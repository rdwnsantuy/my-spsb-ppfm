<?php

namespace App\Http\Controllers\Ujian;

use App\Http\Controllers\Controller;
use App\Models\PaketUjian;
use Illuminate\Http\Request;

class DaftarUjianController extends Controller
{
    /**
     * Tampilkan daftar paket untuk pendaftar.
     *
     * NOTE:
     * - JANGAN memfilter berdasarkan attempt user di sini.
     *   Izin ulang dihitung di view via \App\Services\UjianQuota.
     * - Untuk memastikan baris paket selalu terlihat (debug mudah),
     *   JANGAN filter periode di controller. Biarkan view yang menentukan
     *   tombol aktif/tidak berdasarkan periode & kuota.
     */
    public function index(Request $request)
    {
        $pakets = PaketUjian::query()
            ->orderBy('mulai_pada', 'asc')
            ->orderBy('nama_paket', 'asc')
            ->get([
                'id',
                'nama_paket',
                'durasi_menit',
                'mulai_pada',
                'selesai_pada',
            ]);

        return view('pendaftar.ujian.index', compact('pakets'));
    }
}
