<?php

namespace App\Services;

use App\Models\PaketUjian;
use App\Models\PercobaanUjian;
use App\Models\PaketKategori;
use App\Models\Soal;
use App\Models\OpsiJawaban;
use App\Models\JawabanUjian;

class UjianSnapshotService
{
    /**
     * Generate snapshot soal & opsi untuk satu percobaan.
     * - Ambil N soal per kategori dari paket_kategori
     * - Acak soal/opsi jika di paket diset
     * - Relabel opsi A–E sesuai urutan setelah diacak
     * - Insert ke jawaban_ujian dengan urutan 1..N
     *
     * @throws \RuntimeException jika stok soal kategori kurang dari jumlah_soal
     */
    public function generate(PercobaanUjian $attempt, PaketUjian $paket): void
    {
        // Ambil pengaturan per kategori untuk paket ini
        $pkList = PaketKategori::where('paket_id', $paket->id)->get();
        if ($pkList->isEmpty()) {
            throw new \RuntimeException('Paket belum memiliki konfigurasi kategori.');
        }

        $records = [];

        foreach ($pkList as $pk) {
            // Hitung stok
            $stok = Soal::where('kategori_id', $pk->kategori_id)
                ->where('status_aktif', true)
                ->count();

            if ($stok < $pk->jumlah_soal) {
                throw new \RuntimeException("Bank soal kategori ID {$pk->kategori_id} kurang dari {$pk->jumlah_soal} butir.");
            }

            // Ambil N soal acak
            $soals = Soal::where('kategori_id', $pk->kategori_id)
                ->where('status_aktif', true)
                ->inRandomOrder()
                ->take($pk->jumlah_soal)
                ->get();

            foreach ($soals as $s) {
                // Ambil opsi asli
                $opsi = OpsiJawaban::where('soal_id', $s->id)->get()->map(fn($o) => [
                    'label'     => $o->label,      // label asli (A..E)
                    'teks_opsi' => $o->teks_opsi,
                    'benar'     => (bool) $o->benar,
                ])->toArray();

                // Acak opsi?
                if ($paket->acak_opsi) {
                    shuffle($opsi);
                }

                // RELABEL A–E sesuai urutan baru
                $labels = ['A','B','C','D','E'];
                foreach ($opsi as $i => &$row) {
                    $row['label'] = $labels[$i] ?? $labels[$i % 5];
                }
                unset($row);

                $records[] = [
                    'percobaan_id'       => $attempt->id,
                    'soal_id'            => $s->id,
                    'teks_soal_snapshot' => $s->teks_soal,
                    'opsi_snapshot'      => json_encode($opsi),
                    'opsi_dipilih'       => null,
                    'benar'              => null,
                    'skor_diperoleh'     => 0,
                    'kategori_id'        => $s->kategori_id,
                    'urutan_soal'        => 0, // diisi nanti
                    'bobot'              => $s->bobot ?? 1,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }
        }

        // Acak urutan soal keseluruhan?
        if ($paket->acak_soal) shuffle($records);

        // Isi urutan 1..N
        foreach ($records as $i => &$r) $r['urutan_soal'] = $i + 1;
        unset($r);

        if (!empty($records)) {
            JawabanUjian::insert($records);
        }
    }
}
