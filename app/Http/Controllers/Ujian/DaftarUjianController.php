<?php

namespace App\Http\Controllers\Ujian;

use App\Http\Controllers\Controller;
use App\Models\PaketUjian;
use Illuminate\Http\Request;

class DaftarUjianController extends Controller
{
    public function index(Request $request)
    {
        // ambil semua paket yang sedang aktif berdasarkan periode
        $now = now();
        $pakets = PaketUjian::where(function ($q) use ($now) {
                $q->whereNull('mulai_pada')->orWhere('mulai_pada', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('selesai_pada')->orWhere('selesai_pada', '>=', $now);
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('pendaftar.ujian.index', compact('pakets'));
    }
}
