<?php

namespace App\Http\Controllers;

use App\Models\DataDiri;
use App\Models\Wali;
use App\Models\PendidikanTujuan;
use App\Models\InformasiPsb;
use App\Models\Prestasi;
use App\Models\PenyakitKebutuhanKhusus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PendaftarController extends Controller
{
    /** ===== DEFAULT /pendaftar ===== */
    public function home()
{
    $u = auth()->user();
    return $u->hasCompletedForm()
        ? redirect()->route('pendaftar.jadwal')
        : redirect()->route('pendaftar.daftar');
}

    /** ===== MENU BIASA ===== */
    public function index()
    {
        return view('pendaftar.index');
    }

    public function jadwal()
    {
        return view('pendaftar.jadwal');
    }

    public function status()
    {
        return view('pendaftar.status');
    }

    /** ===== DATA PENDAFTAR (READ) ===== */
    public function dataPendaftar()
    {
        $uid = Auth::id();

        $wali       = Wali::where('user_id', $uid)->orderBy('hubungan_wali')->get();
        $dataDiri   = DataDiri::where('user_id', $uid)->first();
        $tujuan     = PendidikanTujuan::where('user_id', $uid)->first();
        $psb        = InformasiPsb::where('user_id', $uid)->first();
        $prestasi   = Prestasi::where('user_id', $uid)->first();
        $kebutuhan  = PenyakitKebutuhanKhusus::where('user_id', $uid)->first();

        return view('pendaftar.data-pendaftar', compact(
            'wali','dataDiri','tujuan','psb','prestasi','kebutuhan'
        ));
    }

    /** ===== DATA PENDAFTAR (EDIT) -> pakai form yang sama ===== */
    public function editDataPendaftar()
    {
        return $this->daftarForm(); // tampilkan form dengan data terisi (edit mode)
    }

    /** ===== DAFTAR PENDAFTAR : LAPIS 1 (WELCOME) ===== */
    public function daftarWelcome()
{
    return view('pendaftar.daftar-welcome'); // buat view simple berisi ajakan isi form + tombol ke route('pendaftar.daftar.form')
}

    /** ===== DAFTAR PENDAFTAR : LAPIS 2 (FORM) ===== */
    public function daftarForm()
{
    $uid = \Illuminate\Support\Facades\Auth::id();

    $data = [
        'wali'      => \App\Models\Wali::where('user_id', $uid)->first(),
        'dataDiri'  => \App\Models\DataDiri::where('user_id', $uid)->first(),
        'tujuan'    => \App\Models\PendidikanTujuan::where('user_id', $uid)->first(),
        'psb'       => \App\Models\InformasiPsb::where('user_id', $uid)->first(),
        'prestasi'  => \App\Models\Prestasi::where('user_id', $uid)->first(),
        'kebutuhan' => \App\Models\PenyakitKebutuhanKhusus::where('user_id', $uid)->first(),
    ];

    $optPendidikanTujuan = [
        'SMP dalam Pesantren',
        'SMA dalam Pesantren',
        'Perguruan Tinggi',
        'Tidak Sekolah',
        'Lainnya',
    ];
    $optHubunganWali = ['ayah','ibu','wali','lainnya'];
    $optInfoPsb      = ['facebook','instagram','tiktok','website','teman','brosur','lainnya'];
    $optTingkat      = ['ringan','sedang','berat'];

    return view('pendaftar.daftar-form', array_merge($data, compact(
        'optPendidikanTujuan','optHubunganWali','optInfoPsb','optTingkat'
    )));
}


    /** ===== SUBMIT FORM DAFTAR (UPSERT) ===== */
    public function storeDaftarPesantren(Request $r)
    {
        $uid = Auth::id();

        $existingDataDiri = DataDiri::where('user_id', $uid)->first();

        $validated = $r->validate([
            // Wali
            'nama_wali'          => ['required','string','max:255'],
            'hubungan_wali'      => ['required', Rule::in(['ayah','ibu','wali','lainnya'])],
            'rerata_penghasilan' => ['nullable','integer','min:0'],
            'no_telp_wali'       => ['nullable','string','max:20'],

            // Data diri
            'nama_lengkap'       => ['required','string','max:255'],
            'jenis_kelamin'      => ['required', Rule::in(['L','P'])],
            'kabupaten_lahir'    => ['required','string','max:100'],
            'tanggal_lahir'      => ['required','date'],
            'foto_diri'          => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'nisn'               => [
                'nullable','string','max:20',
                Rule::unique('data_diris','nisn')->ignore($existingDataDiri?->id),
            ],
            'alamat_domisili'    => ['required','string','max:255'],
            'foto_kk'            => ['nullable','mimes:jpg,jpeg,png,webp,pdf','max:4096'],
            'no_kk'              => ['nullable','string','max:32'],

            // Pendidikan tujuan
            'pendidikan_tujuan'  => ['required', Rule::in([
                'SMP dalam Pesantren','SMA dalam Pesantren','Perguruan Tinggi','Tidak Sekolah','Lainnya',
            ])],

            // Informasi PSB
            'informasi_psb'      => ['required', Rule::in(['facebook','instagram','tiktok','website','teman','brosur','lainnya'])],

            // Prestasi
            'prestasi_i'         => ['nullable','string','max:255'],
            'prestasi_ii'        => ['nullable','string','max:255'],
            'prestasi_iii'       => ['nullable','string','max:255'],

            // Penyakit & kebutuhan khusus
            'deskripsi'          => ['nullable','string'],
            'tingkat'            => ['nullable', Rule::in(['ringan','sedang','berat'])],
        ]);

        DB::transaction(function () use ($r, $uid) {
            $fotoDiriPath = $r->hasFile('foto_diri')
                ? $r->file('foto_diri')->store('berkas/foto_diri', 'public')
                : null;
            $fotoKkPath = $r->hasFile('foto_kk')
                ? $r->file('foto_kk')->store('berkas/foto_kk', 'public')
                : null;

            Wali::updateOrCreate(
                ['user_id' => $uid, 'hubungan_wali' => $r->hubungan_wali],
                [
                    'nama_wali'          => $r->nama_wali,
                    'rerata_penghasilan' => $r->rerata_penghasilan,
                    'no_telp'            => $r->no_telp_wali,
                ]
            );

            $data = [
                'nama_lengkap'    => $r->nama_lengkap,
                'jenis_kelamin'   => $r->jenis_kelamin,
                'kabupaten_lahir' => $r->kabupaten_lahir,
                'tanggal_lahir'   => $r->tanggal_lahir,
                'nisn'            => $r->nisn,
                'alamat_domisili' => $r->alamat_domisili,
                'no_kk'           => $r->no_kk,
            ];
            if ($fotoDiriPath) $data['foto_diri'] = $fotoDiriPath;
            if ($fotoKkPath)   $data['foto_kk']   = $fotoKkPath;

            DataDiri::updateOrCreate(['user_id' => $uid], $data);

            PendidikanTujuan::updateOrCreate(
                ['user_id' => $uid],
                ['pendidikan_tujuan' => $r->pendidikan_tujuan]
            );

            InformasiPsb::updateOrCreate(
                ['user_id' => $uid],
                ['informasi_psb' => $r->informasi_psb]
            );

            Prestasi::updateOrCreate(
                ['user_id' => $uid],
                [
                    'prestasi_i'   => $r->prestasi_i,
                    'prestasi_ii'  => $r->prestasi_ii,
                    'prestasi_iii' => $r->prestasi_iii,
                ]
            );

            if ($r->filled('deskripsi') || $r->filled('tingkat')) {
                PenyakitKebutuhanKhusus::updateOrCreate(
                    ['user_id' => $uid],
                    [
                        'deskripsi' => $r->deskripsi,
                        'tingkat'   => $r->tingkat,
                    ]
                );
            }
        });

        return redirect()->route('pendaftar.jadwal')
        ->with('ok', 'Form pendaftaran berhasil disimpan.');
    }
}
