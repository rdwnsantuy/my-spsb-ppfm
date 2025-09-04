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

public function dataPendaftar()
{
    $uid = Auth::id();
    $wali      = Wali::where('user_id', $uid)->orderBy('hubungan_wali')->get();
    $dataDiri  = DataDiri::where('user_id', $uid)->first();
    $tujuan    = PendidikanTujuan::where('user_id', $uid)->first();
    $psb       = InformasiPsb::where('user_id', $uid)->first();
    $prestasi  = Prestasi::where('user_id', $uid)->first();
    $kebutuhan = PenyakitKebutuhanKhusus::where('user_id', $uid)->first();

    return view('pendaftar.data-pendaftar', compact('wali','dataDiri','tujuan','psb','prestasi','kebutuhan'));
}



    /**
     * GET: /pendaftar/daftar-pesantren
     * Tampilkan form (prefill jika data sudah ada).
     */
    public function daftarPesantren()
    {
        $uid = Auth::id();

        $data = [
            'wali'      => Wali::where('user_id', $uid)->first(), // satu wali untuk form ini
            'dataDiri'  => DataDiri::where('user_id', $uid)->first(),
            'tujuan'    => PendidikanTujuan::where('user_id', $uid)->first(),
            'psb'       => InformasiPsb::where('user_id', $uid)->first(),
            'prestasi'  => Prestasi::where('user_id', $uid)->first(),
            'kebutuhan' => PenyakitKebutuhanKhusus::where('user_id', $uid)->first(),
        ];

        // Opsi enum di form
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

        return view('pendaftar.daftar-pesantren', array_merge($data, compact(
            'optPendidikanTujuan','optHubunganWali','optInfoPsb','optTingkat'
        )));
    }

    /**
     * POST: /pendaftar/daftar-pesantren
     * Simpan seluruh data formulir pendaftaran (wali, data diri, tujuan, psb, prestasi, penyakit).
     */
    public function storeDaftarPesantren(Request $r)
    {
        $uid = Auth::id();

        // untuk rule unique nisn saat update
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

            // Prestasi (opsional)
            'prestasi_i'         => ['nullable','string','max:255'],
            'prestasi_ii'        => ['nullable','string','max:255'],
            'prestasi_iii'       => ['nullable','string','max:255'],

            // Penyakit & kebutuhan khusus (opsional)
            'deskripsi'          => ['nullable','string'],
            'tingkat'            => ['nullable', Rule::in(['ringan','sedang','berat'])],
        ]);

        DB::transaction(function () use ($r, $uid) {
            // Upload file (jika ada) ke disk 'public'
            $fotoDiriPath = $r->hasFile('foto_diri')
                ? $r->file('foto_diri')->store('berkas/foto_diri', 'public')
                : null;
            $fotoKkPath = $r->hasFile('foto_kk')
                ? $r->file('foto_kk')->store('berkas/foto_kk', 'public')
                : null;

            // WALI (single berdasarkan hubungan_wali)
            Wali::updateOrCreate(
                ['user_id' => $uid, 'hubungan_wali' => $r->hubungan_wali],
                [
                    'nama_wali'          => $r->nama_wali,
                    'rerata_penghasilan' => $r->rerata_penghasilan,
                    'no_telp'            => $r->no_telp_wali,
                ]
            );

            // DATA DIRI (1–1)
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

            // PENDIDIKAN TUJUAN (1–1)
            PendidikanTujuan::updateOrCreate(
                ['user_id' => $uid],
                ['pendidikan_tujuan' => $r->pendidikan_tujuan]
            );

            // INFORMASI PSB (anggap 1–1; kalau mau multi, ubah jadi create baru)
            InformasiPsb::updateOrCreate(
                ['user_id' => $uid],
                ['informasi_psb' => $r->informasi_psb]
            );

            // PRESTASI (1 baris 3 kolom)
            Prestasi::updateOrCreate(
                ['user_id' => $uid],
                [
                    'prestasi_i'   => $r->prestasi_i,
                    'prestasi_ii'  => $r->prestasi_ii,
                    'prestasi_iii' => $r->prestasi_iii,
                ]
            );

            // PENYAKIT & KEBUTUHAN KHUSUS (1–1 untuk form ini)
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

        return back()->with('ok', 'Form pendaftaran berhasil disimpan.');
    }
}
