<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminController
{
    public function index(): View
    {
        return view('admin.index');
    }

        public function verifikasiPembayaran()
    {
        return view('admin.verifikasi-pembayaran');  // buat view di langkah 3
    }

        public function jadwalSeleksi()
    {
        return view('admin.jadwal-seleksi');  // buat view di langkah 3
    }

        public function dataPendaftar()
    {
        return view('admin.data-pendaftar');  // buat view di langkah 3
    }

        public function soalSeleksi()
    {
        return view('admin.soal-seleksi');  // buat view di langkah 3
    }
}
