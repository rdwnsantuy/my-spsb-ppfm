<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriSoal;
use App\Models\Soal;
use App\Models\OpsiJawaban;

class SoalDanOpsiSeeder extends Seeder
{
    public function run(): void
    {
        // ===== Kategori referensi =====
        $tajwid   = KategoriSoal::where('nama_kategori', 'Pengetahuan Tajwid')->firstOrFail();
        $tulis    = KategoriSoal::where('nama_kategori', 'Teori Penulisan Ayat')->firstOrFail();
        $islam    = KategoriSoal::where('nama_kategori', 'Pengetahuan Islam')->firstOrFail();

        // Helper membuat soal + opsi (idempotent)
        $buat = function ($kategori, $teks, $kunciLabel, array $opsi, $tingkat = 'sedang', $bobot = 1) {
            $soal = Soal::updateOrCreate(
                ['kategori_id' => $kategori->id, 'teks_soal' => $teks],
                ['tingkat_kesulitan' => $tingkat, 'bobot' => $bobot, 'status_aktif' => true]
            );

            // Hapus opsi lama agar sinkron (aman untuk rerun)
            OpsiJawaban::where('soal_id', $soal->id)->delete();

            foreach ($opsi as $label => $teks_opsi) {
                OpsiJawaban::create([
                    'soal_id'   => $soal->id,
                    'label'     => $label,            // A/B/C/D/E
                    'teks_opsi' => $teks_opsi,
                    'benar'     => ($label === $kunciLabel),
                    'penjelasan'=> null,
                ]);
            }
        };

        // ===== Contoh Soal Tajwid (2) =====
        $buat($tajwid,
            'Hukum bacaan nun sukun atau tanwin yang bertemu huruf م adalah ...',
            'B',
            ['A' => 'Idzhar', 'B' => 'Ikhfa Syafawi', 'C' => 'Idgham Bighunnah', 'D' => 'Iqlab']
        );

        $buat($tajwid,
            'Makhraj huruf ق (qaf) terletak pada ...',
            'C',
            ['A' => 'Ujung lidah dan gigi seri atas', 'B' => 'Tengah lidah dan langit-langit',
             'C' => 'Pangkal lidah dan langit-langit', 'D' => 'Bibir atas dan bawah']
        );

        // ===== Contoh Soal Teori Penulisan Ayat (2) =====
        $buat($tulis,
            'Tanda panjang (mad) biasanya dilambangkan dengan ...',
            'A',
            ['A' => ' ــٰـ ', 'B' => ' ـً ', 'C' => ' ـٍ ', 'D' => ' ـُ ']
        );

        $buat($tulis,
            'Bentuk huruf ي (ya) di akhir kata tanpa titik ditulis ...',
            'D',
            ['A' => 'ىٰ', 'B' => 'ئ', 'C' => 'ي', 'D' => 'ى']
        );

        // ===== Contoh Soal Pengetahuan Islam (2) =====
        $buat($islam,
            'Jumlah rukun iman adalah ...',
            'B',
            ['A' => 'Lima', 'B' => 'Enam', 'C' => 'Tujuh', 'D' => 'Delapan']
        );

        $buat($islam,
            'Puasa Ramadhan hukumnya bagi muslim yang baligh dan berakal adalah ...',
            'A',
            ['A' => 'Wajib', 'B' => 'Sunnah', 'C' => 'Mubah', 'D' => 'Makruh']
        );
    }
}
