<?php

namespace App\Http\Controllers\Ujian;

use App\Http\Controllers\Controller;
use App\Models\PercobaanUjian;
use Illuminate\Http\Request;

class HasilUjianController extends Controller
{
    /** Tampilkan hasil 1 percobaan ujian milik user login */
    public function show(Request $request, int $percobaan)
    {
        $attempt = PercobaanUjian::with([
                'paket.kategori.kategori', // paket_kategori + kategori referensinya
                'nilaiKategori.kategori',
                'jawaban'
            ])
            ->where('id', $percobaan)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return view('pendaftar.ujian.hasil', ['attempt' => $attempt]);
    }
}
