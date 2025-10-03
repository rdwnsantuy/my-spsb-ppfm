<?php

namespace App\Http\Controllers\Ujian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Models
use App\Models\PercobaanUjian;
use App\Models\JawabanUjian;
use App\Models\NilaiKategori;
use App\Models\PaketKategori;

class HasilUjianController extends Controller
{
    /**
     * Tampilkan hasil 1 percobaan ujian milik user yang login.
     *
     * Route disarankan:
     *   GET /pendaftar/ujian/hasil/{percobaan}
     *   -> name: pendaftar.ujian.result
     *   -> param {percobaan} di-bind ke model PercobaanUjian
     */
    public function show(Request $request, PercobaanUjian $percobaan)
    {
        // Pastikan percobaan ini milik user yang login
        abort_unless($percobaan->user_id === $request->user()->id, 404);

        // Jika masih berlangsung/dibuat atau waktu habis, finalisasi dulu agar skor muncul konsisten
        if (in_array($percobaan->status, ['dibuat', 'berlangsung'])) {
            $this->finalizeAttempt($percobaan);
        } elseif (
            $percobaan->selesai_pada &&
            now()->gt($percobaan->selesai_pada) &&
            $percobaan->status !== 'kadaluarsa' &&
            $percobaan->status !== 'selesai'
        ) {
            // waktu habis tapi status belum ditandai
            $this->finalizeAttempt($percobaan, forceExpire: true);
        }

        // Muat relasi yang dibutuhkan view hasil
        $percobaan->load([
            'paket.kategori.kategori', // paket_kategori + referensi kategori
            'nilaiKategori.kategori',  // nilai per kategori
            'jawaban',                 // seluruh jawaban (jika ingin pakai di view)
        ]);

        // Kirim ke view hasil
        return view('pendaftar.ujian.hasil', [
            'attempt' => $percobaan,
        ]);
    }

    /**
     * Finalisasi attempt:
     * - Tandai status (selesai/kadaluarsa)
     * - Hitung benar per-butir + skor_diperoleh
     * - Rekap NilaiKategori
     * - Hitung skor total tertimbang + status kelulusan
     */
    protected function finalizeAttempt(PercobaanUjian $attempt, bool $forceExpire = false): void
    {
        $attempt->loadMissing('paket');

        DB::transaction(function () use ($attempt, $forceExpire) {
            // Tentukan status akhir
            if ($forceExpire || ($attempt->selesai_pada && now()->gt($attempt->selesai_pada))) {
                $attempt->status = 'kadaluarsa';
            } else {
                $attempt->status = 'selesai';
            }

            // 1) Penilaian per butir
            $items = JawabanUjian::where('percobaan_id', $attempt->id)->get();

            foreach ($items as $item) {
                // $item->opsi_snapshot dicast ke array di Model
                $opsi   = collect($item->opsi_snapshot ?? []);
                $kunci  = $opsi->firstWhere('benar', true);
                $benar  = false;

                if ($item->opsi_dipilih && $kunci) {
                    $benar = ($item->opsi_dipilih === ($kunci['label'] ?? null));
                }

                $item->benar           = $benar;                          // kolom boolean
                $item->skor_diperoleh  = $benar ? ($item->bobot ?? 1) : 0; // skor per-butir
                $item->save();
            }

            // 2) Rekap per kategori
            NilaiKategori::where('percobaan_id', $attempt->id)->delete();

            $byKategori = $items->groupBy('kategori_id');
            $ringkas    = []; // [kategori_id => [max, got, pct, lulus]]

            foreach ($byKategori as $kategoriId => $grup) {
                $max = (float) $grup->sum('bobot');
                $got = (float) $grup->sum('skor_diperoleh');
                $pct = $max > 0 ? round($got / $max * 100, 2) : 0.0;

                $ringkas[$kategoriId] = [
                    'poin_maksimal'  => $max,
                    'poin_diperoleh' => $got,
                    'persentase'     => $pct,
                    'lulus'          => true, // diisi setelah cek ambang
                ];
            }

            // Bobot & ambang kategori dari paket
            $pkList = PaketKategori::where('paket_id', $attempt->paket_id)->get()->keyBy('kategori_id');

            // 3) Kelulusan per kategori + skor total tertimbang
            $total = 0.0;
            foreach ($ringkas as $kategoriId => $val) {
                $pk     = $pkList->get($kategoriId);
                $bobot  = $pk ? (float) $pk->bobot_kategori : 0.0; // persen
                $ambang = $pk && !is_null($pk->ambang_kelulusan) ? (float) $pk->ambang_kelulusan : null;

                $lulusKategori = is_null($ambang) ? true : ($val['persentase'] >= $ambang);
                $ringkas[$kategoriId]['lulus'] = $lulusKategori;

                $total += $val['persentase'] * ($bobot / 100.0);
            }

            // Simpan NilaiKategori
            foreach ($ringkas as $kategoriId => $val) {
                NilaiKategori::create([
                    'percobaan_id'   => $attempt->id,
                    'kategori_id'    => $kategoriId,
                    'poin_diperoleh' => $val['poin_diperoleh'],
                    'poin_maksimal'  => $val['poin_maksimal'],
                    'persentase'     => $val['persentase'],
                    'lulus'          => $val['lulus'],
                ]);
            }

            // 4) Kelulusan paket
            $strategi    = $attempt->paket->strategi_kelulusan; // total_saja | total_dan_semua_kategori | khusus
            $ambangTotal = (float) ($attempt->paket->ambang_kelulusan_total ?? 0);

            $lulus = false;
            if ($strategi === 'total_saja') {
                $lulus = ($total >= $ambangTotal);
            } elseif ($strategi === 'total_dan_semua_kategori') {
                $semuaKategoriLulus = collect($ringkas)->every(fn ($x) => $x['lulus'] === true);
                $lulus = ($total >= $ambangTotal) && $semuaKategoriLulus;
            } else {
                // strategi 'khusus' → samakan dulu dengan total_saja
                $lulus = ($total >= $ambangTotal);
            }

            // 5) Simpan skor & status
            $attempt->skor_total = round($total, 2);
            $attempt->lulus      = $lulus;
            $attempt->save();
        });
    }
}
