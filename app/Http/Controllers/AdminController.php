<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminController
{
    public function index(): View
    {
        return view('admin.index');
    }
}
