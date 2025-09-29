<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriSoal;
use Illuminate\Http\Request;

class KategoriSoalController extends Controller
{
    public function index()
    {
        $rows = KategoriSoal::latest()->paginate(15);
        return view('admin.ujian.kategori.index', compact('rows'));
    }

    public function create()
    {
        return view('admin.ujian.kategori.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_kategori' => 'required|string|max:100',
            'deskripsi'     => 'nullable|string',
        ]);

        KategoriSoal::create($data);
        return redirect()->route('admin.kategori-soal.index')->with('ok', 'Kategori dibuat.');
    }

    public function edit(KategoriSoal $kategori)
    {
        return view('admin.ujian.kategori.edit', compact('kategori'));
    }

    public function update(Request $request, KategoriSoal $kategori)
    {
        $data = $request->validate([
            'nama_kategori' => 'required|string|max:100',
            'deskripsi'     => 'nullable|string',
        ]);

        $kategori->update($data);
        return redirect()->route('admin.kategori-soal.index')->with('ok', 'Kategori diperbarui.');
    }

    public function destroy(KategoriSoal $kategori)
    {
        $kategori->delete();
        return back()->with('ok', 'Kategori dihapus.');
    }
}
