<?php

namespace App\Http\Controllers\Ujian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Service snapshot (sampling soal + acak opsi + relabel A–E + insert ke jawaban_ujian)
use App\Services\UjianSnapshotService;

// Models
use App\Models\PaketUjian;
use App\Models\PaketKategori;
use App\Models\PercobaanUjian;
use App\Models\JawabanUjian;
use App\Models\NilaiKategori;

class PengerjaanUjianController extends Controller
{
    /**
     * Mulai ujian: validasi kuota attempt, generate snapshot soal, set durasi.
     */
    public function start(Request $request, int $paket)
    {
        $user = $request->user();

        /** @var PaketUjian $paketUjian */
        $paketUjian = PaketUjian::with(['kategori']) // relasi ke paket_kategori
            ->findOrFail($paket);

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

        // Cek maksimal percobaan
        if ($paketUjian->maksimal_percobaan > 0) {
            $sudah = PercobaanUjian::where('user_id', $user->id)
                ->where('paket_id', $paketUjian->id)
                ->count();

            if ($sudah >= $paketUjian->maksimal_percobaan) {
                return back()->with('error', 'Maksimal percobaan ujian telah tercapai.');
            }
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
            // Bank soal tidak mencukupi kuota/validasi gagal → tampilkan pesan jelas
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

    $attempt = \App\Models\PercobaanUjian::with(['paket'])
        ->where('id', $percobaan)
        ->where('user_id', $user->id)
        ->firstOrFail();

    // Jika waktu habis → expire & arahkan ke hasil
    if ($attempt->status === 'berlangsung' &&
        $attempt->selesai_pada &&
        now()->gt($attempt->selesai_pada)) {
        $this->forceExpire($attempt);
        return redirect()->route('pendaftar.ujian.result', $attempt->id)
            ->with('warning', 'Waktu ujian telah habis.');
    }

    if (! in_array($attempt->status, ['berlangsung', 'dibuat'])) {
        return redirect()->route('pendaftar.ujian.result', $attempt->id);
    }

    // Pastikan snapshot ada
    $total = \App\Models\JawabanUjian::where('percobaan_id', $attempt->id)->count();
    if ($total === 0) {
        return redirect()->route('pendaftar.ujian.start', $attempt->paket_id)
            ->with('error', 'Snapshot soal belum tersedia. Silakan mulai ulang ujian.');
    }

    // Clamp urutan agar 1..$total
    $urutan = max(1, min($urutan, $total));

    $item = \App\Models\JawabanUjian::where('percobaan_id', $attempt->id)
        ->where('urutan_soal', $urutan)
        ->first();

    if (! $item) {
        // fallback aman ke soal pertama
        return redirect()->route('pendaftar.ujian.show', [$attempt->id, 1]);
    }

    $allowBack = (bool) $attempt->paket->boleh_kembali;
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
     * Simpan jawaban untuk 1 soal (AJAX/POST normal).
     * request: opsi_dipilih = 'A'|'B'|'C'|'D'|'E'
     */
    public function saveAnswer(Request $request, int $percobaan)
    {
        $request->validate([
            'jawaban_id'   => 'required|integer',
            'opsi_dipilih' => 'nullable|in:A,B,C,D,E',
        ]);

        $jawaban = JawabanUjian::where('id', $request->input('jawaban_id'))
            ->where('percobaan_id', $percobaan)
            ->firstOrFail();

        // Pastikan milik user yang sedang login
        $this->authorizeAttemptOwnership($jawaban->percobaan_id);

        // Cek waktu
        $attempt = PercobaanUjian::findOrFail($percobaan);
        if ($attempt->selesai_pada && now()->gt($attempt->selesai_pada)) {
            $this->forceExpire($attempt);
            return response()->json(['ok' => false, 'message' => 'Waktu habis. Jawaban tidak disimpan.'], 440);
        }

        // Simpan pilihan
        $jawaban->opsi_dipilih = $request->input('opsi_dipilih'); // boleh null (kosongkan)
        $jawaban->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Submit ujian → autograde, hitung nilai_kategori & skor_total.
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

        // Jika waktu habis ketika submit → tetap proses nilai & tandai.
        if ($attempt->selesai_pada && now()->gt($attempt->selesai_pada)) {
            $attempt->status = 'kadaluarsa';
        } else {
            $attempt->status = 'selesai';
        }

        DB::transaction(function () use ($attempt) {
            [$skorTotal, $lulus] = $this->hitungNilaiDanLulus($attempt);
            $attempt->skor_total = $skorTotal;
            $attempt->lulus      = $lulus;
            $attempt->save();
        });

        return redirect()->route('pendaftar.ujian.result', $attempt->id)
            ->with('ok', 'Ujian telah dikumpulkan.');
    }

    /**
     * Halaman hasil.
     * (Jika rute hasil sudah dialihkan ke HasilUjianController, method ini boleh tidak dipakai.)
     */
    public function result(Request $request, int $percobaan)
    {
        $attempt = PercobaanUjian::with([
                'paket.kategori.kategori', // paket_kategori + kategori referensinya
                'nilaiKategori.kategori',
                'jawaban' // jika ingin tampilkan pembahasan per butir
            ])
            ->where('id', $percobaan)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Jika sedang berlangsung & waktu habis → expire dulu agar konsisten
        if ($attempt->status === 'berlangsung' &&
            $attempt->selesai_pada &&
            now()->gt($attempt->selesai_pada)) {
            $this->forceExpire($attempt);
        }

        return view('pendaftar.ujian.hasil', [
            'attempt' => $attempt,
        ]);
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
            // $item->opsi_snapshot sudah dicast ke array di Model (casts)
            $opsi  = collect($item->opsi_snapshot);
            $kunci = $opsi->firstWhere('benar', true);
            $isBenar = $item->opsi_dipilih && $kunci && ($item->opsi_dipilih === ($kunci['label'] ?? null));

            $item->benar = $isBenar;
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
                'lulus'          => false, // isi nanti setelah tau ambang kategori
            ];
        }

        // Muat bobot & ambang dari paket_kategori
        $pkList = PaketKategori::where('paket_id', $attempt->paket_id)->get()->keyBy('kategori_id');

        // 3) Tentukan lulus per kategori & hitung skor total tertimbang
        $total = 0.0;
        foreach ($nilaiKategori as $kategoriId => $val) {
            $pk     = $pkList->get($kategoriId);
            $bobot  = $pk ? (float) $pk->bobot_kategori : 0.0;
            $ambang = $pk && !is_null($pk->ambang_kelulusan) ? (float) $pk->ambang_kelulusan : null;

            $lulusKategori = is_null($ambang) ? true : ($val['persentase'] >= $ambang);
            $nilaiKategori[$kategoriId]['lulus'] = $lulusKategori;

            // total tertimbang (persentase * bobot%)
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
            $semuaKategoriLulus = collect($nilaiKategori)->every(fn ($x) => $x['lulus'] === true);
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
