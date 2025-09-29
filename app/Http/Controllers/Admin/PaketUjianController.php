<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaketUjian;
use App\Models\PaketKategori;
use App\Models\KategoriSoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaketUjianController extends Controller
{
    public function index()
    {
        $rows = PaketUjian::withCount('percobaan')->latest()->paginate(15);
        return view('admin.ujian.paket.index', compact('rows'));
    }

    public function create()
    {
        $kategories = KategoriSoal::orderBy('nama_kategori')->get();
        return view('admin.ujian.paket.create', compact('kategories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_paket'              => 'required|string|max:150',
            'deskripsi'               => 'nullable|string',
            'durasi_menit'            => 'required|integer|min:1|max:300',
            'mulai_pada'              => 'nullable|date',
            'selesai_pada'            => 'nullable|date|after_or_equal:mulai_pada',
            'acak_soal'               => 'required|boolean',
            'acak_opsi'               => 'required|boolean',
            'boleh_kembali'           => 'required|boolean',
            'maksimal_percobaan'      => 'required|integer|min:0|max:10',
            'ambang_kelulusan_total'  => 'nullable|integer|min:0|max:100',
            'strategi_kelulusan'      => 'required|in:total_saja,total_dan_semua_kategori,khusus',
            // per-kategori
            'kategori'                        => 'required|array|min:1',
            'kategori.*.id'                   => 'required|exists:kategori_soal,id',
            'kategori.*.jumlah_soal'          => 'required|integer|min:1|max:200',
            'kategori.*.bobot_kategori'       => 'required|integer|min:0|max:100',
            'kategori.*.ambang_kelulusan'     => 'nullable|integer|min:0|max:100',
        ]);

        DB::transaction(function () use ($data) {
            $paket = PaketUjian::create([
                'nama_paket'             => $data['nama_paket'],
                'deskripsi'              => $data['deskripsi'] ?? null,
                'durasi_menit'           => $data['durasi_menit'],
                'mulai_pada'             => $data['mulai_pada'] ?? null,
                'selesai_pada'           => $data['selesai_pada'] ?? null,
                'acak_soal'              => $data['acak_soal'],
                'acak_opsi'              => $data['acak_opsi'],
                'boleh_kembali'          => $data['boleh_kembali'],
                'maksimal_percobaan'     => $data['maksimal_percobaan'],
                'ambang_kelulusan_total' => $data['ambang_kelulusan_total'] ?? null,
                'strategi_kelulusan'     => $data['strategi_kelulusan'],
            ]);

            foreach ($data['kategori'] as $row) {
                PaketKategori::create([
                    'paket_id'         => $paket->id,
                    'kategori_id'      => $row['id'],
                    'jumlah_soal'      => $row['jumlah_soal'],
                    'bobot_kategori'   => $row['bobot_kategori'],
                    'ambang_kelulusan' => $row['ambang_kelulusan'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.paket.index')->with('ok', 'Paket ujian dibuat.');
    }

    public function edit(PaketUjian $paket)
    {
        $paket->load('kategori');
        $kategories = KategoriSoal::orderBy('nama_kategori')->get();
        return view('admin.ujian.paket.edit', compact('paket', 'kategories'));
    }

    public function update(Request $request, PaketUjian $paket)
    {
        $data = $request->validate([
            'nama_paket'              => 'required|string|max:150',
            'deskripsi'               => 'nullable|string',
            'durasi_menit'            => 'required|integer|min:1|max:300',
            'mulai_pada'              => 'nullable|date',
            'selesai_pada'            => 'nullable|date|after_or_equal:mulai_pada',
            'acak_soal'               => 'required|boolean',
            'acak_opsi'               => 'required|boolean',
            'boleh_kembali'           => 'required|boolean',
            'maksimal_percobaan'      => 'required|integer|min:0|max:10',
            'ambang_kelulusan_total'  => 'nullable|integer|min:0|max:100',
            'strategi_kelulusan'      => 'required|in:total_saja,total_dan_semua_kategori,khusus',
            // per-kategori
            'kategori'                        => 'required|array|min:1',
            'kategori.*.id'                   => 'required|exists:kategori_soal,id',
            'kategori.*.jumlah_soal'          => 'required|integer|min:1|max:200',
            'kategori.*.bobot_kategori'       => 'required|integer|min:0|max:100',
            'kategori.*.ambang_kelulusan'     => 'nullable|integer|min:0|max:100',
        ]);

        DB::transaction(function () use ($paket, $data) {
            $paket->update([
                'nama_paket'             => $data['nama_paket'],
                'deskripsi'              => $data['deskripsi'] ?? null,
                'durasi_menit'           => $data['durasi_menit'],
                'mulai_pada'             => $data['mulai_pada'] ?? null,
                'selesai_pada'           => $data['selesai_pada'] ?? null,
                'acak_soal'              => $data['acak_soal'],
                'acak_opsi'              => $data['acak_opsi'],
                'boleh_kembali'          => $data['boleh_kembali'],
                'maksimal_percobaan'     => $data['maksimal_percobaan'],
                'ambang_kelulusan_total' => $data['ambang_kelulusan_total'] ?? null,
                'strategi_kelulusan'     => $data['strategi_kelulusan'],
            ]);

            // sinkronisasi paket_kategori
            PaketKategori::where('paket_id', $paket->id)->delete();
            foreach ($data['kategori'] as $row) {
                PaketKategori::create([
                    'paket_id'         => $paket->id,
                    'kategori_id'      => $row['id'],
                    'jumlah_soal'      => $row['jumlah_soal'],
                    'bobot_kategori'   => $row['bobot_kategori'],
                    'ambang_kelulusan' => $row['ambang_kelulusan'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.paket.index')->with('ok', 'Paket diperbarui.');
    }

    public function destroy(PaketUjian $paket)
    {
        $paket->delete();
        return back()->with('ok', 'Paket dihapus.');
    }
}
