<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriSoal;
use App\Models\Soal;
use App\Models\OpsiJawaban;
use Illuminate\Http\Request;

class SoalController extends Controller
{
    public function index(Request $request)
    {
        $q = Soal::with('kategori')->latest();

        if ($request->filled('kategori_id')) {
            $q->where('kategori_id', $request->kategori_id);
        }

        $rows = $q->paginate(15);
        $kategories = KategoriSoal::orderBy('nama_kategori')->get();
        return view('admin.ujian.soal.index', compact('rows', 'kategories'));
    }

    public function create()
    {
        $kategories = KategoriSoal::orderBy('nama_kategori')->get();
        return view('admin.ujian.soal.create', compact('kategories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kategori_id'        => 'required|exists:kategori_soal,id',
            'teks_soal'          => 'required|string',
            'media'              => 'nullable|string',
            'tingkat_kesulitan'  => 'required|in:mudah,sedang,sulit',
            'bobot'              => 'required|integer|min:1|max:10',
            'status_aktif'       => 'required|boolean',
            // opsi
            'opsi'               => 'required|array|min:2',
            'opsi.*.label'       => 'required|string|in:A,B,C,D,E',
            'opsi.*.teks'        => 'required|string',
            'kunci'              => 'required|string|in:A,B,C,D,E',
        ]);

        $soal = Soal::create([
            'kategori_id'       => $data['kategori_id'],
            'teks_soal'         => $data['teks_soal'],
            'media'             => $data['media'] ?? null,
            'tingkat_kesulitan' => $data['tingkat_kesulitan'],
            'bobot'             => $data['bobot'],
            'status_aktif'      => $data['status_aktif'],
        ]);

        // simpan opsi
        foreach ($data['opsi'] as $row) {
            OpsiJawaban::create([
                'soal_id'   => $soal->id,
                'label'     => $row['label'],
                'teks_opsi' => $row['teks'],
                'benar'     => $row['label'] === $data['kunci'],
            ]);
        }

        return redirect()->route('admin.soal.index')->with('ok', 'Soal dibuat.');
    }

    public function edit(Soal $soal)
    {
        $soal->load('opsi');
        $kategories = KategoriSoal::orderBy('nama_kategori')->get();
        return view('admin.ujian.soal.edit', compact('soal', 'kategories'));
    }

    public function update(Request $request, Soal $soal)
    {
        $data = $request->validate([
            'kategori_id'        => 'required|exists:kategori_soal,id',
            'teks_soal'          => 'required|string',
            'media'              => 'nullable|string',
            'tingkat_kesulitan'  => 'required|in:mudah,sedang,sulit',
            'bobot'              => 'required|integer|min:1|max:10',
            'status_aktif'       => 'required|boolean',
            // opsi
            'opsi'               => 'required|array|min:2',
            'opsi.*.id'          => 'nullable|integer',
            'opsi.*.label'       => 'required|string|in:A,B,C,D,E',
            'opsi.*.teks'        => 'required|string',
            'kunci'              => 'required|string|in:A,B,C,D,E',
        ]);

        $soal->update([
            'kategori_id'       => $data['kategori_id'],
            'teks_soal'         => $data['teks_soal'],
            'media'             => $data['media'] ?? null,
            'tingkat_kesulitan' => $data['tingkat_kesulitan'],
            'bobot'             => $data['bobot'],
            'status_aktif'      => $data['status_aktif'],
        ]);

        // sinkronisasi opsi: hapus dulu, tulis ulang (paling aman)
        OpsiJawaban::where('soal_id', $soal->id)->delete();
        foreach ($data['opsi'] as $row) {
            OpsiJawaban::create([
                'soal_id'   => $soal->id,
                'label'     => $row['label'],
                'teks_opsi' => $row['teks'],
                'benar'     => $row['label'] === $data['kunci'],
            ]);
        }

        return redirect()->route('admin.soal.index')->with('ok', 'Soal diperbarui.');
    }

    public function destroy(Soal $soal)
    {
        $soal->delete();
        return back()->with('ok', 'Soal dihapus.');
    }
}
