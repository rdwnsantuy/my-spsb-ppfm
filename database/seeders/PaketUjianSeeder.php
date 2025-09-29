<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaketUjian;
use App\Models\PaketKategori;
use App\Models\KategoriSoal;

class PaketUjianSeeder extends Seeder
{
    public function run(): void
    {
        $paket = PaketUjian::updateOrCreate(
            ['nama_paket' => 'Tes Tulis PSB'],
            [
                'deskripsi'                 => 'Tes tulis seleksi PSB (tajwid, penulisan ayat, pengetahuan Islam)',
                'durasi_menit'              => 45,
                'mulai_pada'                => now()->subDay(),
                'selesai_pada'              => now()->addMonth(),
                'acak_soal'                 => true,
                'acak_opsi'                 => true,
                'boleh_kembali'             => false,
                'maksimal_percobaan'        => 1,
                'ambang_kelulusan_total'    => 70,
                'strategi_kelulusan'        => 'total_dan_semua_kategori',
            ]
        );

        // Bobot & kuota per kategori (sesuaikan kebutuhan)
        $map = [
            ['nama' => 'Pengetahuan Tajwid',   'jumlah' => 2, 'bobot' => 40, 'ambang' => 60],
            ['nama' => 'Teori Penulisan Ayat', 'jumlah' => 2, 'bobot' => 30, 'ambang' => 60],
            ['nama' => 'Pengetahuan Islam',    'jumlah' => 2, 'bobot' => 30, 'ambang' => 60],
        ];

        foreach ($map as $m) {
            $kategori = KategoriSoal::where('nama_kategori', $m['nama'])->firstOrFail();

            PaketKategori::updateOrCreate(
                ['paket_id' => $paket->id, 'kategori_id' => $kategori->id],
                ['jumlah_soal' => $m['jumlah'], 'bobot_kategori' => $m['bobot'], 'ambang_kelulusan' => $m['ambang']]
            );
        }
    }
}
