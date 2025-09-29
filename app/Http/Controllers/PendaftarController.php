<?php

namespace App\Http\Controllers;

use App\Models\DataDiri;
use App\Models\Wali;
use App\Models\PendidikanTujuan;
use App\Models\InformasiPsb;
use App\Models\Prestasi;
use App\Models\PenyakitKebutuhanKhusus;
use App\Models\PembayaranPendaftaran;
use App\Models\PembayaranDaftarUlang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class PendaftarController extends Controller
{
    /* =========================================================
     |  Sumber kebenaran opsi (HARUS match enum di DB)
     |=========================================================*/
    // VALUE yang disimpan ke DB:
    private array $OPT_PENDIDIKAN_TUJUAN = ['SMP','SMA','MA','SMK','Lainnya']; // <- sesuai enum DB

    // Label cantik untuk tampilan:
    private array $OPT_PENDIDIKAN_TUJUAN_LABEL = [
        'SMP'     => 'SMP dalam Pesantren',
        'SMA'     => 'SMA dalam Pesantren',
        'MA'      => 'Madrasah Aliyah (MA)',
        'SMK'     => 'Sekolah Menengah Kejuruan (SMK)',
        'Lainnya' => 'Lainnya',
    ];

    private array $OPT_HUBUNGAN_WALI = ['ayah','ibu','wali','lainnya'];
    private array $OPT_INFO_PSB      = ['facebook','instagram','tiktok','website','teman','brosur','lainnya'];
    private array $OPT_TINGKAT       = ['ringan','sedang','berat'];

    /* =========================================================
     |  Helpers
     |=========================================================*/
    /** Ambil catatan verifikasi: dukung kolom lama 'catatan' dan baru 'note'. */
    private function getNote(?object $model): ?string
    {
        return $model->note ?? $model->catatan ?? null;
    }

    /* =========================================================
     |  DEFAULT /pendaftar
     |=========================================================*/
    public function home()
    {
        $u = auth()->user();

        if (method_exists($u, 'hasCompletedForm')) {
            return $u->hasCompletedForm()
                ? redirect()->route('pendaftar.jadwal')
                : redirect()->route('pendaftar.daftar');
        }

        $sudahIsi = DataDiri::where('user_id', $u->id)->exists();
        return $sudahIsi
            ? redirect()->route('pendaftar.jadwal')
            : redirect()->route('pendaftar.daftar');
    }

    /** (opsional) */
    public function index()
    {
        return view('pendaftar.index');
    }

    /**
     * JADWAL: gate akses ujian & upload bukti pembayaran pendaftaran
     */
    public function jadwal()
    {
        $uid    = Auth::id();
        $bayarP = PembayaranPendaftaran::where('user_id', $uid)->latest()->first();

        $blocked = false;
        $reason  = null;
        $note    = null;

        if (! $bayarP) {
            $blocked = true;
            $reason  = 'Kamu belum mengunggah bukti pembayaran pendaftaran.';
        } elseif ($bayarP->status === 'pending') {
            $blocked = true;
            $reason  = 'Pembayaran pendaftaran masih menunggu verifikasi admin.';
            $note    = $this->getNote($bayarP);
        } elseif ($bayarP->status === 'rejected') {
            $blocked = true;
            $reason  = 'Pembayaran pendaftaran ditolak. Silakan unggah ulang bukti yang valid.';
            $note    = $this->getNote($bayarP);
        }

        return view('pendaftar.jadwal', compact('blocked','reason','note','bayarP'));
    }

    /**
     * STATUS: upload bukti daftar ulang & lihat status
     */
    public function status()
    {
        $uid    = Auth::id();
        $bayarP = PembayaranPendaftaran::where('user_id', $uid)->latest()->first();
        $bayarU = PembayaranDaftarUlang::where('user_id', $uid)->latest()->first();

        return view('pendaftar.status', compact('bayarP','bayarU'));
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

    /** ===== EDIT DATA -> pakai form yang sama ===== */
    public function editDataPendaftar()
    {
        return $this->daftarForm(); // view akan tahu ini edit lewat route name
    }

    /** ===== DAFTAR PENDAFTAR : LAPIS 1 (WELCOME) ===== */
    public function daftarWelcome()
    {
        return view('pendaftar.daftar-welcome');
    }

    /** ===== DAFTAR PENDAFTAR : LAPIS 2 (FORM) ===== */
    public function daftarForm()
    {
        $uid = Auth::id();

        $data = [
            'wali'      => Wali::where('user_id', $uid)->first(),
            'dataDiri'  => DataDiri::where('user_id', $uid)->first(),
            'tujuan'    => PendidikanTujuan::where('user_id', $uid)->first(),
            'psb'       => InformasiPsb::where('user_id', $uid)->first(),
            'prestasi'  => Prestasi::where('user_id', $uid)->first(),
            'kebutuhan' => PenyakitKebutuhanKhusus::where('user_id', $uid)->first(),
        ];

        // === info untuk view ===
        $isEdit = request()->routeIs('pendaftar.data-pendaftar.edit');
        $valTanggal = old('tanggal_lahir');
        if (! $valTanggal) {
            $raw = $data['dataDiri']->tanggal_lahir ?? null;
            $valTanggal = $raw ? Carbon::parse($raw)->format('Y-m-d') : '';
        }

        return view('pendaftar.daftar-form', array_merge($data, [
            'optPendidikanTujuan'      => $this->OPT_PENDIDIKAN_TUJUAN,
            'optPendidikanTujuanLabel' => $this->OPT_PENDIDIKAN_TUJUAN_LABEL,
            'optHubunganWali'          => $this->OPT_HUBUNGAN_WALI,
            'optInfoPsb'               => $this->OPT_INFO_PSB,
            'optTingkat'               => $this->OPT_TINGKAT,
            'isEdit'                   => $isEdit,
            'valTanggal'               => $valTanggal,
        ]));
    }

    /** ===== SUBMIT FORM DAFTAR (UPSERT) ===== */
    public function storeDaftarPesantren(Request $r)
    {
        $uid = Auth::id();
        $existingDataDiri = DataDiri::where('user_id', $uid)->first();
        $wasEditing       = (bool) $existingDataDiri;

        // --- Sanitasi ringan: trim semua string ---
        $sanitized = [];
        foreach ($r->all() as $k => $v) {
            $sanitized[$k] = is_string($v) ? trim($v) : $v;
        }
        $r->merge($sanitized);

        // --- VALIDASI: semua opsi pakai sumber di atas ---
        $r->validate([
            // Wali
            'nama_wali'          => ['required','string','max:255'],
            'hubungan_wali'      => ['required', Rule::in($this->OPT_HUBUNGAN_WALI)],
            'rerata_penghasilan' => ['nullable','integer','min:0'],
            'no_telp_wali'       => ['nullable','string','max:20'],

            // Data diri
            'nama_lengkap'       => ['required','string','max:255'],
            'jenis_kelamin'      => ['required', Rule::in(['L','P'])],
            'kabupaten_lahir'    => ['required','string','max:100'],
            'tanggal_lahir'      => ['required','date'],
            'foto_diri'          => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'nisn'               => ['nullable','string','max:20', Rule::unique('data_diris','nisn')->ignore($existingDataDiri?->id)],
            'alamat_domisili'    => ['required','string','max:255'],
            'foto_kk'            => ['nullable','mimes:jpg,jpeg,png,webp,pdf','max:4096'],
            'no_kk'              => ['nullable','string','max:32'],

            // Pendidikan tujuan (HARUS match enum DB)
            'pendidikan_tujuan'  => ['required', Rule::in($this->OPT_PENDIDIKAN_TUJUAN)],

            // Informasi PSB
            'informasi_psb'      => ['required', Rule::in($this->OPT_INFO_PSB)],

            // Prestasi
            'prestasi_i'         => ['nullable','string','max:255'],
            'prestasi_ii'        => ['nullable','string','max:255'],
            'prestasi_iii'       => ['nullable','string','max:255'],

            // Penyakit & kebutuhan khusus
            'deskripsi'          => ['nullable','string'],
            'tingkat'            => ['nullable', Rule::in($this->OPT_TINGKAT)],
        ]);

        DB::transaction(function () use ($r, $uid) {
            // Upload file (disk public)
            $fotoDiriPath = $r->hasFile('foto_diri') ? $r->file('foto_diri')->store('berkas/foto_diri', 'public') : null;
            $fotoKkPath   = $r->hasFile('foto_kk')   ? $r->file('foto_kk')->store('berkas/foto_kk', 'public')   : null;

            // WALI
            Wali::updateOrCreate(
                ['user_id' => $uid, 'hubungan_wali' => $r->hubungan_wali],
                [
                    'nama_wali'          => $r->nama_wali,
                    'rerata_penghasilan' => $r->rerata_penghasilan,
                    'no_telp'            => $r->no_telp_wali,
                ]
            );

            // DATA DIRI
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

            // PENDIDIKAN TUJUAN (string enum yang valid)
            PendidikanTujuan::updateOrCreate(
                ['user_id' => $uid],
                ['pendidikan_tujuan' => $r->pendidikan_tujuan]
            );

            // INFORMASI PSB
            InformasiPsb::updateOrCreate(
                ['user_id' => $uid],
                ['informasi_psb' => $r->informasi_psb]
            );

            // PRESTASI
            Prestasi::updateOrCreate(
                ['user_id' => $uid],
                [
                    'prestasi_i'   => $r->prestasi_i,
                    'prestasi_ii'  => $r->prestasi_ii,
                    'prestasi_iii' => $r->prestasi_iii,
                ]
            );

            // PENYAKIT / KEBUTUHAN KHUSUS (opsional)
            if ($r->filled('deskripsi') || $r->filled('tingkat')) {
                PenyakitKebutuhanKhusus::updateOrCreate(
                    ['user_id' => $uid],
                    ['deskripsi' => $r->deskripsi, 'tingkat' => $r->tingkat]
                );
            }
        });

        // Redirect yang pasti benar: berdasarkan apakah sebelumnya sudah ada data diri
        return $wasEditing
            ? redirect()->route('pendaftar.data-pendaftar')->with('ok', 'Data berhasil diperbarui.')
            : redirect()->route('pendaftar.jadwal')->with('ok', 'Form pendaftaran berhasil disimpan.');
    }
}
