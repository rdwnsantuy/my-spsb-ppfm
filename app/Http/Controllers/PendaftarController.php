<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PendaftarController
{
    public function index(): View
    {
        return view('pendaftar.index');
    }
}
