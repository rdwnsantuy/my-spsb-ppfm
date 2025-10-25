<?php

namespace App\Http\Controllers;

use App\Models\PembayaranPendaftaran;
use App\Models\PembayaranDaftarUlang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function verifikasiPembayaran()
    {
        // gabungkan 2 tabel untuk ditampilkan pada satu tabel di view
        $pendaftaran = PembayaranPendaftaran::with('user')
            ->latest()->get()->map(function ($r) {
                return (object)[
                    'id'          => $r->id,
                    'jenis'       => 'pendaftaran',
                    'user'        => $r->user,
                    'foto_bukti'  => $r->foto_bukti,
                    'status'      => $r->status,     // enum: pending|accepted|rejected
                    'verified_by' => $r->verified_by,
                    'verified_at' => $r->verified_at,
                    // pakai note jika ada, fallback ke catatan bila skema lama
                    'note'        => $r->note ?? $r->catatan,
                    'created_at'  => $r->created_at,
                ];
            });

        $daftarUlang = PembayaranDaftarUlang::with('user')
            ->latest()->get()->map(function ($r) {
                return (object)[
                    'id'          => $r->id,
                    'jenis'       => 'daftar-ulang',
                    'user'        => $r->user,
                    'foto_bukti'  => $r->foto_bukti,
                    'status'      => $r->status,
                    'verified_by' => $r->verified_by,
                    'verified_at' => $r->verified_at,
                    'note'        => $r->note ?? $r->catatan,
                    'created_at'  => $r->created_at,
                ];
            });

        $items = $pendaftaran->merge($daftarUlang)->sortByDesc('created_at');

        return view('admin.verifikasi-pembayaran', compact('items'));
    }

    public function jadwalSeleksi()
    {
        return view('admin.jadwal-seleksi');
    }

    /**
     * Data Pendaftar (admin): listing + filter + pagination.
     * URL: /admin/data-pendaftar
     *
     * Kolom yang ditampilkan:
     * - Pendaftar (name, email, username)
     * - Pendidikan Tujuan (dari pendidikan_tujuans)
     * - Status pembayaran pendaftaran & daftar ulang (pakai pembayaran terakhir per user)
     * - Flag "Terdaftar" = keduanya accepted
     */
    public function dataPendaftar(Request $request)
    {
        $q        = trim((string) $request->get('q', ''));
        $tujuan   = $request->get('tujuan');          // SMP|SMA|MA|SMK|Lainnya|Semua
        $statPend = $request->get('status_pend');     // pending|accepted|rejected|Semua
        $statDu   = $request->get('status_du');       // pending|accepted|rejected|Semua

        // --- Subquery: pembayaran pendaftaran terakhir (per user)
        $subLastPend = DB::table('pembayaran_pendaftarans as t')
            ->select('t.user_id', DB::raw('MAX(t.id) as last_id'))
            ->groupBy('t.user_id');

        $pend = DB::table('pembayaran_pendaftarans as p')
            ->joinSub($subLastPend, 'sp', function ($j) {
                $j->on('p.user_id', '=', 'sp.user_id')->on('p.id', '=', 'sp.last_id');
            })
            ->select('p.user_id', 'p.status');

        // --- Subquery: pembayaran daftar ulang terakhir (per user)
        $subLastDu = DB::table('pembayaran_daftar_ulangs as t')
            ->select('t.user_id', DB::raw('MAX(t.id) as last_id'))
            ->groupBy('t.user_id');

        $du = DB::table('pembayaran_daftar_ulangs as d')
            ->joinSub($subLastDu, 'sd', function ($j) {
                $j->on('d.user_id', '=', 'sd.user_id')->on('d.id', '=', 'sd.last_id');
            })
            ->select('d.user_id', 'd.status');

        // --- Query utama
        $rows = DB::table('users as u')
            ->leftJoin('data_diris as dd', 'dd.user_id', '=', 'u.id')
            ->leftJoin('pendidikan_tujuans as pt', 'pt.user_id', '=', 'u.id')
            ->leftJoinSub($pend, 'pend', 'pend.user_id', '=', 'u.id')
            ->leftJoinSub($du,   'du',   'du.user_id',   '=', 'u.id')
            ->where('u.role', 'pendaftar')
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('u.name', 'like', "%{$q}%")
                      ->orWhere('u.email', 'like', "%{$q}%")
                      ->orWhere('u.username', 'like', "%{$q}%")
                      ->orWhere('dd.nama_lengkap', 'like', "%{$q}%");
                });
            })
            ->when($tujuan && $tujuan !== 'Semua', fn ($w) => $w->where('pt.pendidikan_tujuan', $tujuan))
            ->when($statPend && $statPend !== 'Semua', fn ($w) => $w->where('pend.status', $statPend))
            ->when($statDu && $statDu !== 'Semua', fn ($w) => $w->where('du.status', $statDu))
            ->orderBy('u.id', 'desc')
            ->select([
                'u.id',
                'u.name',
                'u.email',
                'u.username',
                'pt.pendidikan_tujuan',
                DB::raw('COALESCE(pend.status,"-") as status_pendaftaran'),
                DB::raw('COALESCE(du.status,"-")   as status_daftar_ulang'),
            ])
            ->paginate(15)
            ->appends($request->query());

        // opsi dropdown
        $opsTujuan = ['Semua','SMP','SMA','MA','SMK','Lainnya'];
        $opsStatus = ['Semua','pending','accepted','rejected'];

        return view('admin.data-pendaftar', [
            'rows'       => $rows,
            'q'          => $q,
            'tujuan'     => $tujuan ?: 'Semua',
            'statusPend' => $statPend ?: 'Semua',
            'statusDu'   => $statDu   ?: 'Semua',
            'opsTujuan'  => $opsTujuan,
            'opsStatus'  => $opsStatus,
        ]);
    }

    public function soalSeleksi()
    {
        return view('admin.soal-seleksi');
    }

    /** ====== AKSI VERIFIKASI ====== */

    public function terimaPembayaran(string $jenis, int $id)
    {
        $row = $this->findByJenis($jenis, $id);

        $row->status      = 'accepted';
        // tulis ke kolom 'note' bila ada, jika tidak ada pakai 'catatan'
        if ($this->hasColumn($row, 'note')) {
            $row->note = null;
        } elseif ($this->hasColumn($row, 'catatan')) {
            $row->catatan = null;
        }

        $row->verified_by = Auth::id();
        $row->verified_at = now();
        $row->save();

        return back()->with('ok', 'Pembayaran diterima.');
    }

    public function tolakPembayaran(string $jenis, int $id, Request $r)
    {
        $r->validate([
            'alasan' => ['required','string','max:500'], // pakai name="alasan" di form
        ]);

        $row = $this->findByJenis($jenis, $id);
        $row->status      = 'rejected';

        // simpan alasan ke field yang benar
        if ($this->hasColumn($row, 'note')) {
            $row->note = $r->input('alasan');
        } elseif ($this->hasColumn($row, 'catatan')) {
            $row->catatan = $r->input('alasan');
        }

        $row->verified_by = Auth::id();
        $row->verified_at = now();
        $row->save();

        return back()->with('ok', 'Pembayaran ditolak dengan catatan.');
    }

    private function findByJenis(string $jenis, int $id)
    {
        return $jenis === 'daftar-ulang'
            ? PembayaranDaftarUlang::findOrFail($id)
            : PembayaranPendaftaran::findOrFail($id);
    }

    /**
     * Cek cepat apakah model punya kolom tertentu (berdasar atribut yang ter-load).
     * Ini menghindari update ke kolom yang tidak ada (yang menyebabkan error 1054).
     */
    private function hasColumn($model, string $attr): bool
    {
        // Jika atribut sudah ada di array attributes(), kita anggap kolomnya ada.
        return array_key_exists($attr, $model->getAttributes());
    }
}
