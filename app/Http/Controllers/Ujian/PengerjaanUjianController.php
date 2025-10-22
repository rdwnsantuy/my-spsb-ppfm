<?php

namespace App\Http\Controllers\Ujian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Service snapshot (sampling soal + acak opsi + relabel A–E + insert ke jawaban_ujian)
use App\Services\UjianSnapshotService;
// Service kuota & izin ulang (BARU)
use App\Services\UjianQuota;

// Models
use App\Models\PaketUjian;
use App\Models\PaketKategori;
use App\Models\PercobaanUjian;
use App\Models\JawabanUjian;
use App\Models\NilaiKategori;

class PengerjaanUjianController extends Controller
{
    /**
     * Mulai ujian: validasi kuota attempt/izin ulang, generate snapshot soal, set durasi.
     */
    public function start(Request $request, int $paket)
    {
        $user = $request->user();

        /** @var PaketUjian $paketUjian */
        $paketUjian = PaketUjian::with(['kategori'])->findOrFail($paket);

        // Cek periode aktif
        $now = now();
        if (($paketUjian->mulai_pada && $now->lt($paketUjian->mulai_pada)) ||
            ($paketUjian->selesai_pada && $now->gt($paketUjian->selesai_pada))) {
            return back()->with('error', 'Paket ujian belum/tidak lagi aktif.');
        }

        // Cek attempt berjalan untuk paket ini → lanjutkan kalau ada
        $existing = PercobaanUjian::where('user_id', $user->id)
            ->where('paket_id', $paketUjian->id)
            ->whereIn('status', ['dibuat', 'berlangsung'])
            ->latest('id')
            ->first();

        if ($existing) {
            // jika waktu habis, tandai kadaluarsa dan arahkan ke result
            if ($existing->selesai_pada && now()->gt($existing->selesai_pada)) {
                $this->forceExpire($existing);
                return redirect()->route('pendaftar.ujian.result', $existing->id)
                    ->with('warning', 'Waktu ujian telah habis.');
            }

            // lanjutkan ke soal pertama yang belum dijawab
            $nextUrutan = JawabanUjian::where('percobaan_id', $existing->id)
                ->whereNull('opsi_dipilih')
                ->min('urutan_soal');

            // jika semua sudah terjawab, langsung ke hasil
            if (is_null($nextUrutan)) {
                return redirect()->route('pendaftar.ujian.result', $existing->id);
            }

            return redirect()->route('pendaftar.ujian.show', [$existing->id, $nextUrutan]);
        }

        // =========================
        // CEK KUOTA + IZIN ULANG (BARU)
        // =========================
        // Logika: total allowed = paket.maksimal_percobaan + kuota_tambahan (izin admin yang masih aktif).
        // User hanya boleh mulai attempt baru jika:
        // - tidak ada attempt 'berlangsung' (sudah dicek di atas), DAN
        // - usedAttempts < allowedAttempts
        if (! UjianQuota::canStartNewAttempt($user->id, $paketUjian)) {
            return back()->with('error', 'Kuota ujian Anda sudah habis. Hubungi panitia untuk izin ujian ulang.');
        }

        // Generate attempt & snapshot soal via Service
        try {
            $attempt = DB::transaction(function () use ($user, $paketUjian) {
                $attempt = PercobaanUjian::create([
                    'paket_id'     => $paketUjian->id,
                    'user_id'      => $user->id,
                    'status'       => 'berlangsung',
                    'mulai_pada'   => now(),
                    'selesai_pada' => now()->copy()->addMinutes($paketUjian->durasi_menit),
                    'ip_address'   => request()->ip(),
                    'user_agent'   => request()->userAgent(),
                ]);

                // sampling + snapshot (dengan relabel A–E)
                app(UjianSnapshotService::class)->generate($attempt, $paketUjian);

                return $attempt;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pendaftar.ujian.show', [$attempt->id, 1]);
    }

    /**
     * Tampilkan 1 soal (berdasarkan urutan).
     */
    public function show(Request $request, int $percobaan, int $urutan)
    {
        $user = $request->user();

        $attempt = PercobaanUjian::with(['paket'])
            ->where('id', $percobaan)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Jika waktu habis → expire & arahkan ke hasil
        if (
            $attempt->status === 'berlangsung' &&
            $attempt->selesai_pada &&
            now()->gt($attempt->selesai_pada)
        ) {
            $this->forceExpire($attempt);
            return redirect()->route('pendaftar.ujian.result', $attempt->id)
                ->with('warning', 'Waktu ujian telah habis.');
        }

        if (! in_array($attempt->status, ['berlangsung', 'dibuat'])) {
            return redirect()->route('pendaftar.ujian.result', $attempt->id);
        }

        // Pastikan snapshot ada
        $total = JawabanUjian::where('percobaan_id', $attempt->id)->count();
        if ($total === 0) {
            return redirect()->route('pendaftar.ujian.start', $attempt->paket_id)
                ->with('error', 'Snapshot soal belum tersedia. Silakan mulai ulang ujian.');
        }

        // Clamp urutan agar 1..$total
        $urutan = max(1, min($urutan, $total));

        $item = JawabanUjian::where('percobaan_id', $attempt->id)
            ->where('urutan_soal', $urutan)
            ->first();

        if (! $item) {
            // fallback aman ke soal pertama
            return redirect()->route('pendaftar.ujian.show', [$attempt->id, 1]);
        }

        $allowBack = (bool) ($attempt->paket->boleh_kembali ?? true);
        $prev = $allowBack ? max(1, $urutan - 1) : null;
        $next = ($urutan < $total) ? $urutan + 1 : null;

        return view('pendaftar.ujian.kerjakan', [
            'attempt'   => $attempt,
            'item'      => $item,
            'total'     => $total,
            'urutan'    => $urutan,
            'prev'      => $prev,
            'next'      => $next,
            'allowBack' => $allowBack,
            'sisaDetik' => max(0, optional($attempt->selesai_pada)->diffInSeconds(now(), false) * -1),
        ]);
    }

    /**
     * Simpan jawaban untuk 1 soal (POST normal).
     * Menerima:
     * - jawaban_id (wajib)
     * - opsi_dipilih (nullable)
     * - urutan (wajib untuk redirect balik)
     * - action=save|next  (atau nav=stay|next, kompatibel lama)
     */
    public function saveAnswer(Request $r, int $percobaanId)
    {
        $r->validate([
            'jawaban_id'   => ['required', 'integer'],
            'opsi_dipilih' => ['nullable', 'string', 'max:10'],
            'urutan'       => ['required', 'integer', 'min:1'],
            'action'       => ['nullable', 'in:save,next'],
            'nav'          => ['nullable', 'in:stay,next'], // kompatibel view lama
        ]);

        $attempt = PercobaanUjian::where('id', $percobaanId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Cegah update kalau sudah ditutup
        if (in_array($attempt->status, ['selesai', 'kadaluarsa'])) {
            return back()->with('error', 'Percobaan sudah ditutup.');
        }

        $jawaban = JawabanUjian::where('id', $r->jawaban_id)
            ->where('percobaan_id', $attempt->id)
            ->firstOrFail();

        // Simpan / update pilihan
        $jawaban->opsi_dipilih = $r->filled('opsi_dipilih') ? $r->opsi_dipilih : null;
        $jawaban->save();

        // Tentukan aksi dari tombol
        $act = $r->input('action');
        if (!$act) {
            // kompatibel dengan form lama yang kirim "nav=stay|next"
            $nav = $r->input('nav');
            $act = $nav === 'next' ? 'next' : 'save';
        }

        // Hitung next berdasarkan urutan + total (tidak perlu hidden next di form)
        $urutan = (int) $r->input('urutan', 1);
        $total  = JawabanUjian::where('percobaan_id', $attempt->id)->count();
        $next   = $urutan < $total ? $urutan + 1 : null;

        if ($act === 'next' && $next) {
            return redirect()
                ->route('pendaftar.ujian.show', [$attempt->id, $next])
                ->with('ok', 'Jawaban tersimpan.');
        }

        // default: tetap di soal ini
        return redirect()
            ->route('pendaftar.ujian.show', [$attempt->id, $urutan])
            ->with('ok', 'Jawaban tersimpan.');
    }

    /**
     * Submit ujian → sinkronkan bulk jawaban (answers_json), autograde, hitung nilai_kategori & skor_total.
     * View baru mengirim semua jawaban sekaligus via hidden field "answers_json".
     *
     * Request:
     * - answers_json (opsional) → JSON object: { "<jawaban_id>": "A"|"B"|"C"|"D"|"E"|null, ... }
     */
    public function submit(Request $request, int $percobaan)
    {
        $attempt = PercobaanUjian::with(['paket', 'jawaban', 'nilaiKategori'])
            ->where('id', $percobaan)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($attempt->status !== 'berlangsung' && $attempt->status !== 'dibuat') {
            return redirect()->route('pendaftar.ujian.result', $attempt->id);
        }

        // tandai status berdasarkan waktu
        if ($attempt->selesai_pada && now()->gt($attempt->selesai_pada)) {
            $attempt->status = 'kadaluarsa';
        } else {
            $attempt->status = 'selesai';
        }
        $attempt->selesai_pada = now();

        DB::transaction(function () use ($request, $attempt) {
            // ====== (BARU) Terima dan sinkronkan jawaban massal sekali kirim ======
            $raw = $request->input('answers_json');
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);

                if (is_array($decoded) && !empty($decoded)) {
                    // Ambil semua id yang dikirim (hanya yang milik percobaan ini yang akan diupdate)
                    $ids = array_map('intval', array_keys($decoded));

                    if (!empty($ids)) {
                        $items = JawabanUjian::where('percobaan_id', $attempt->id)
                            ->whereIn('id', $ids)
                            ->get()->keyBy('id');

                        foreach ($decoded as $jawabanId => $label) {
                            $jawabanId = (int) $jawabanId;
                            if (!isset($items[$jawabanId])) {
                                continue; // abaikan id yang tidak termasuk attempt ini
                            }

                            $val = $label;

                            // Normalisasi: hanya string label pendek (A..E) atau null
                            if (is_string($val)) {
                                $val = trim($val);
                                if ($val === '') {
                                    $val = null;
                                }
                                // batas panjang mengikuti validasi lama
                                if ($val !== null && strlen($val) > 10) {
                                    $val = substr($val, 0, 10);
                                }
                            } else {
                                $val = null;
                            }

                            $row = $items[$jawabanId];
                            // Hanya update bila berubah (hemat write)
                            if ($row->opsi_dipilih !== $val) {
                                $row->opsi_dipilih = $val;
                                $row->save();
                            }
                        }
                    }
                }
            }
            // ====== END sinkronisasi massal ======

            // Hitung nilai per-butir, per-kategori, total, lulus/tidak (existing logic)
            [$skorTotal, $lulus] = $this->hitungNilaiDanLulus($attempt);
            $attempt->skor_total = $skorTotal;
            $attempt->lulus      = $lulus;
            $attempt->save();
        });

        return redirect()
            ->route('pendaftar.ujian.result', $attempt->id)
            ->with('ok', 'Selamat, ujian selesai.');
    }

    /**
     * Halaman hasil (opsional – kalau rute hasil dipakai controller terpisah, method ini boleh tidak dipakai).
     */
    public function result(Request $request, int $percobaan)
    {
        $attempt = PercobaanUjian::with([
            'paket.kategori.kategori',
            'nilaiKategori.kategori',
            'jawaban'
        ])
            ->where('id', $percobaan)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (
            $attempt->status === 'berlangsung' &&
            $attempt->selesai_pada &&
            now()->gt($attempt->selesai_pada)
        ) {
            $this->forceExpire($attempt);
        }

        return view('pendaftar.ujian.hasil', ['attempt' => $attempt]);
    }

    // =========================
    // Helper internal
    // =========================

    /**
     * Hitung nilai per-butir, per-kategori, skor total, dan status lulus.
     * Return: [skorTotal(0..100), lulus(bool)]
     */
    protected function hitungNilaiDanLulus(PercobaanUjian $attempt): array
    {
        // 1) Penilaian per butir (benar/0) berdasarkan snapshot
        $items = JawabanUjian::where('percobaan_id', $attempt->id)->get();

        foreach ($items as $item) {
            $opsi    = collect($item->opsi_snapshot);
            $kunci   = $opsi->firstWhere('benar', true);
            $isBenar = $item->opsi_dipilih && $kunci && ($item->opsi_dipilih === ($kunci['label'] ?? null));

            $item->benar          = $isBenar;
            $item->skor_diperoleh = $isBenar ? ($item->bobot ?? 1) : 0;
            $item->save();
        }

        // 2) Rekap per kategori: reset & hitung ulang
        NilaiKategori::where('percobaan_id', $attempt->id)->delete();

        $byKategori    = $items->groupBy('kategori_id');
        $nilaiKategori = [];

        foreach ($byKategori as $kategoriId => $grup) {
            $max = (float) $grup->sum('bobot');
            $got = (float) $grup->sum('skor_diperoleh');
            $pct = $max > 0 ? round($got / $max * 100, 2) : 0.0;

            $nilaiKategori[$kategoriId] = [
                'poin_maksimal'  => $max,
                'poin_diperoleh' => $got,
                'persentase'     => $pct,
                'lulus'          => false,
            ];
        }

        // Muat bobot & ambang dari paket_kategori
        $pkList = PaketKategori::where('paket_id', $attempt->paket_id)
            ->get()->keyBy('kategori_id');

        // 3) Tentukan lulus per kategori & hitung skor total tertimbang
        $total = 0.0;
        foreach ($nilaiKategori as $kategoriId => $val) {
            $pk     = $pkList->get($kategoriId);
            $bobot  = $pk ? (float) $pk->bobot_kategori : 0.0;
            $ambang = $pk && !is_null($pk->ambang_kelulusan) ? (float) $pk->ambang_kelulusan : null;

            $lulusKategori = is_null($ambang) ? true : ($val['persentase'] >= $ambang);
            $nilaiKategori[$kategoriId]['lulus'] = $lulusKategori;

            $total += $val['persentase'] * ($bobot / 100.0);
        }

        // Simpan nilai_kategori
        foreach ($nilaiKategori as $kategoriId => $val) {
            NilaiKategori::create([
                'percobaan_id'   => $attempt->id,
                'kategori_id'    => $kategoriId,
                'poin_diperoleh' => $val['poin_diperoleh'],
                'poin_maksimal'  => $val['poin_maksimal'],
                'persentase'     => $val['persentase'],
                'lulus'          => $val['lulus'],
            ]);
        }

        // 4) Tentukan kelulusan paket
        $strategi    = $attempt->paket->strategi_kelulusan; // total_saja | total_dan_semua_kategori | khusus
        $ambangTotal = (float) ($attempt->paket->ambang_kelulusan_total ?? 0);

        $lulus = false;
        if ($strategi === 'total_saja') {
            $lulus = ($total >= $ambangTotal);
        } elseif ($strategi === 'total_dan_semua_kategori') {
            $semuaKategoriLulus = collect($nilaiKategori)->every(fn($x) => $x['lulus'] === true);
            $lulus = ($total >= $ambangTotal) && $semuaKategoriLulus;
        } else {
            // strategi 'khusus' → untuk sekarang samakan dengan total_saja
            $lulus = ($total >= $ambangTotal);
        }

        return [round($total, 2), $lulus];
    }

    /**
     * Menandai attempt kadaluarsa & hitung nilai atas jawaban yang ada.
     */
    protected function forceExpire(PercobaanUjian $attempt): void
    {
        if ($attempt->status === 'kadaluarsa' || $attempt->status === 'selesai') {
            return;
        }

        DB::transaction(function () use ($attempt) {
            $attempt->status = 'kadaluarsa';
            [$skor, $lulus] = $this->hitungNilaiDanLulus($attempt);
            $attempt->skor_total = $skor;
            $attempt->lulus = $lulus;
            $attempt->save();
        });
    }

    /**
     * Pastikan percobaan milik user yang login.
     */
    protected function authorizeAttemptOwnership(int $percobaanId): void
    {
        $ownerId = PercobaanUjian::where('id', $percobaanId)->value('user_id');
        abort_unless($ownerId === Auth::id(), 403);
    }
}
