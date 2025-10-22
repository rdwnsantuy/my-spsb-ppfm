<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriSoal;
use App\Models\Soal;
use App\Models\PaketUjian;
use App\Models\PaketKategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SoalAdminController extends Controller
{
    /**
     * Halaman tunggal (tab): Soal, Kategori, Bobot Kategori.
     * View: resources/views/admin/soal/index.blade.php
     */
    public function index(Request $r)
    {
        $q          = trim($r->get('q', ''));
        $kategoriId = $r->get('kategori');
        $paketId    = $r->get('paket');

        $kategori = KategoriSoal::query()
            ->orderBy('nama_kategori')
            ->get();

        $paket = PaketUjian::query()
            ->orderBy('nama_paket')
            ->get();

        $soal = Soal::with('kategori')
            ->when($q, fn ($x) => $x->where('pertanyaan', 'like', "%$q%"))
            ->when($kategoriId, fn ($x) => $x->where('kategori_id', $kategoriId))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $bobot = collect();
        if ($paketId) {
            $bobot = PaketUjian::with(['kategori' => function ($rel) {
                $rel->orderBy('nama_kategori');
            }])->find($paketId)?->kategori ?? collect();
        }

        return view('admin.soal.index', [
            'kategori'       => $kategori,
            'paket'          => $paket,
            'soal'           => $soal,
            'bobot'          => $bobot,
            'paketTerpilih'  => $paketId,
        ]);
    }

    // ==================== KATEGORI ====================
    public function storeCategory(Request $r)
    {
        $data = $r->validate([
            'nama_kategori' => ['required', 'string', 'max:100', Rule::unique('kategori_soal', 'nama_kategori')],
            'deskripsi'     => ['nullable', 'string', 'max:500'],
            'aktif'         => ['sometimes', 'boolean'],
        ]);

        $data['aktif'] = $r->boolean('aktif');
        KategoriSoal::create($data);

        return back()->with('ok', 'Kategori ditambahkan.');
    }

    public function updateCategory(Request $r, KategoriSoal $kategori)
    {
        $data = $r->validate([
            'nama_kategori' => ['required', 'string', 'max:100', Rule::unique('kategori_soal', 'nama_kategori')->ignore($kategori->id)],
            'deskripsi'     => ['nullable', 'string', 'max:500'],
            'aktif'         => ['sometimes', 'boolean'],
        ]);

        $data['aktif'] = $r->boolean('aktif');
        $kategori->update($data);

        return back()->with('ok', 'Kategori diperbarui.');
    }

    public function destroyCategory(KategoriSoal $kategori)
    {
        $kategori->delete();
        return back()->with('ok', 'Kategori dihapus.');
    }

    // ====================== SOAL ======================
    public function storeQuestion(Request $r)
    {
        $data = $r->validate([
            'kategori_id' => ['required', 'exists:kategori_soal,id'],
            'pertanyaan'  => ['required', 'string'],
            'tipe'        => ['required', Rule::in(['pg', 'isian', 'esai'])],
            'opsi'        => ['nullable', 'array'],
            'kunci'       => ['nullable', 'string', 'max:255'],
            'bobot'       => ['required', 'integer', 'min:1', 'max:100'],
            'aktif'       => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'kategori_id' => $data['kategori_id'],
            'pertanyaan'  => $data['pertanyaan'],
            'tipe'        => $data['tipe'],
            'opsi_json'   => $data['tipe'] === 'pg' ? array_values($r->input('opsi', [])) : null,
            'kunci'       => $data['tipe'] === 'esai' ? null : ($data['kunci'] ?? null),
            'bobot'       => $data['bobot'],
            'aktif'       => $r->boolean('aktif'),
        ];

        Soal::create($payload);

        return back()->with('ok', 'Soal dibuat.');
    }

    public function updateQuestion(Request $r, Soal $soal)
    {
        $data = $r->validate([
            'kategori_id' => ['required', 'exists:kategori_soal,id'],
            'pertanyaan'  => ['required', 'string'],
            'tipe'        => ['required', Rule::in(['pg', 'isian', 'esai'])],
            'opsi'        => ['nullable', 'array'],
            'kunci'       => ['nullable', 'string', 'max:255'],
            'bobot'       => ['required', 'integer', 'min:1', 'max:100'],
            'aktif'       => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'kategori_id' => $data['kategori_id'],
            'pertanyaan'  => $data['pertanyaan'],
            'tipe'        => $data['tipe'],
            'opsi_json'   => $data['tipe'] === 'pg' ? array_values($r->input('opsi', [])) : null,
            'kunci'       => $data['tipe'] === 'esai' ? null : ($data['kunci'] ?? null),
            'bobot'       => $data['bobot'],
            'aktif'       => $r->boolean('aktif'),
        ];

        $soal->update($payload);

        return back()->with('ok', 'Soal diperbarui.');
    }

    public function destroyQuestion(Soal $soal)
    {
        $soal->delete();
        return back()->with('ok', 'Soal dihapus.');
    }

    // ============ BOBOT KATEGORI PER PAKET ============
    public function storeWeight(Request $r)
    {
        $data = $r->validate([
            'paket_id'         => ['required', 'exists:paket_ujian,id'],
            'kategori_id'      => ['required', 'exists:kategori_soal,id'],
            'bobot_kategori'   => ['required', 'integer', 'min:0', 'max:100'],
            'ambang_kelulusan' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        /** @var PaketUjian $paket */
        $paket = PaketUjian::findOrFail($data['paket_id']);

        $paket->kategori()->syncWithoutDetaching([
            $data['kategori_id'] => [
                'bobot_kategori'   => $data['bobot_kategori'],
                'ambang_kelulusan' => $data['ambang_kelulusan'] ?? null,
            ],
        ]);

        return back()->with('ok', 'Bobot kategori disimpan.');
    }

    public function updateWeight(Request $r, $pivotId)
    {
        $data = $r->validate([
            'bobot_kategori'   => ['required', 'integer', 'min:0', 'max:100'],
            'ambang_kelulusan' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        // Pakai query langsung agar tidak tergantung model pivot
        DB::table('paket_kategori')->where('id', $pivotId)->update($data);

        return back()->with('ok', 'Bobot kategori diperbarui.');
    }

    public function destroyWeight($pivotId)
    {
        DB::table('paket_kategori')->where('id', $pivotId)->delete();

        return back()->with('ok', 'Relasi paket-kategori dihapus.');
    }
}
