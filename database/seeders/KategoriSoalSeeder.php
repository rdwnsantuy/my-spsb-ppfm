<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriSoal;

class KategoriSoalSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama_kategori' => 'Pengetahuan Tajwid',     'deskripsi' => 'Hukum bacaan, makhraj, sifat huruf.'],
            ['nama_kategori' => 'Teori Penulisan Ayat',   'deskripsi' => 'Dasar-dasar penulisan Arab dan tanda baca.'],
            ['nama_kategori' => 'Pengetahuan Islam',      'deskripsi' => 'Aqidah, fiqih dasar, sejarah singkat.'],
        ];

        foreach ($data as $row) {
            KategoriSoal::updateOrCreate(
                ['nama_kategori' => $row['nama_kategori']],
                ['deskripsi' => $row['deskripsi']]
            );
        }
    }
}
