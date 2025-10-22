<?php

namespace App\Http\Controllers\Admin\Ujian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\KategoriSoal;
use App\Models\Soal;
use App\Models\OpsiJawaban;

class SoalSeleksiController extends Controller
{
    public function index(Request $r)
    {
        $qKategori = KategoriSoal::orderBy('nama_kategori')->get();

        $q = Soal::with('kategori')
            ->when($r->filled('kategori_id'), fn($s) => $s->where('kategori_id', $r->kategori_id))
            ->orderByDesc('id');

        $soal = $q->paginate(10)->withQueryString();

        return view('admin.ujian.soal-seleksi', [
            'kategories' => $qKategori,
            'soal'       => $soal,
            'filter'     => [
                'kategori_id' => (int) $r->get('kategori_id', 0),
            ],
        ]);
    }

    // =======================
    // KATEGORI
    // =======================
    public function storeKategori(Request $r)
    {
        $data = $r->validate([
            'nama_kategori' => ['required','string','max:100'],
            'deskripsi'     => ['nullable','string'],
        ]);

        KategoriSoal::create($data);
        return back()->with('ok', 'Kategori dibuat.');
    }

    public function updateKategori(Request $r, KategoriSoal $kategori)
    {
        $data = $r->validate([
            'nama_kategori' => ['required','string','max:100'],
            'deskripsi'     => ['nullable','string'],
        ]);

        $kategori->update($data);
        return back()->with('ok', 'Kategori diperbarui.');
    }

    public function destroyKategori(KategoriSoal $kategori)
    {
        $kategori->delete();
        return back()->with('ok', 'Kategori dihapus.');
    }

    // =======================
    // SOAL + OPSI
    // =======================
    public function storeSoal(Request $r)
    {
        $data = $r->validate([
            'kategori_id'        => ['required','exists:kategori_soal,id'],
            'teks_soal'          => ['required','string'],
            'tingkat_kesulitan'  => ['required','in:mudah,sedang,sulit'],
            'bobot'              => ['required','integer','min:1','max:10'],
            'status_aktif'       => ['required','boolean'],

            'opsi.A.teks'        => ['required','string'],
            'opsi.B.teks'        => ['required','string'],
            'opsi.C.teks'        => ['required','string'],
            'opsi.D.teks'        => ['required','string'],
            'opsi_benar'         => ['required','in:A,B,C,D'],
        ]);

        DB::transaction(function () use ($data, $r) {
            $soal = Soal::create([
                'kategori_id'       => $data['kategori_id'],
                'teks_soal'         => $data['teks_soal'],
                'tingkat_kesulitan' => $data['tingkat_kesulitan'],
                'bobot'             => $data['bobot'],
                'status_aktif'      => $data['status_aktif'],
            ]);

            foreach (['A','B','C','D'] as $label) {
                OpsiJawaban::create([
                    'soal_id'   => $soal->id,
                    'label'     => $label,
                    'teks_opsi' => $r->input("opsi.$label.teks"),
                    'benar'     => $r->input('opsi_benar') === $label ? 1 : 0,
                ]);
            }
        });

        return back()->with('ok','Soal beserta opsi tersimpan.');
    }

    public function updateSoal(Request $r, Soal $soal)
    {
        $data = $r->validate([
            'kategori_id'        => ['required','exists:kategori_soal,id'],
            'teks_soal'          => ['required','string'],
            'tingkat_kesulitan'  => ['required','in:mudah,sedang,sulit'],
            'bobot'              => ['required','integer','min:1','max:10'],
            'status_aktif'       => ['required','boolean'],

            'opsi.A.teks'        => ['required','string'],
            'opsi.B.teks'        => ['required','string'],
            'opsi.C.teks'        => ['required','string'],
            'opsi.D.teks'        => ['required','string'],
            'opsi_benar'         => ['required','in:A,B,C,D'],
        ]);

        DB::transaction(function () use ($r, $soal) {
            $soal->update([
                'kategori_id'       => $r->kategori_id,
                'teks_soal'         => $r->teks_soal,
                'tingkat_kesulitan' => $r->tingkat_kesulitan,
                'bobot'             => $r->bobot,
                'status_aktif'      => (bool) $r->status_aktif,
            ]);

            $opsi = $soal->opsi()->get()->keyBy('label');
            foreach (['A','B','C','D'] as $label) {
                $row = $opsi->get($label);
                if ($row) {
                    $row->update([
                        'teks_opsi' => $r->input("opsi.$label.teks"),
                        'benar'     => $r->opsi_benar === $label ? 1 : 0,
                    ]);
                } else {
                    OpsiJawaban::create([
                        'soal_id'   => $soal->id,
                        'label'     => $label,
                        'teks_opsi' => $r->input("opsi.$label.teks"),
                        'benar'     => $r->opsi_benar === $label ? 1 : 0,
                    ]);
                }
            }
        });

        return back()->with('ok','Soal diperbarui.');
    }

    public function destroySoal(Soal $soal)
    {
        $soal->delete();
        return back()->with('ok','Soal dihapus.');
    }
}
