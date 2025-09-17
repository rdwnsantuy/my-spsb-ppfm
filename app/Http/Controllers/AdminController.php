<?php

namespace App\Http\Controllers;

use App\Models\PembayaranPendaftaran;
use App\Models\PembayaranDaftarUlang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                    'status'      => $r->status,
                    'verified_by' => $r->verified_by,
                    'verified_at' => $r->verified_at,
                    'catatan'     => $r->catatan,
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
                    'catatan'     => $r->catatan,
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

    public function dataPendaftar()
    {
        return view('admin.data-pendaftar');
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
        $row->catatan     = null;               // bersihkan catatan jika ada
        $row->verified_by = Auth::id();
        $row->verified_at = now();
        $row->save();

        return back()->with('ok', 'Pembayaran diterima.');
    }

    public function tolakPembayaran(string $jenis, int $id, Request $r)
    {
        $r->validate([
            'catatan' => ['required','string','max:500'],
        ]);

        $row = $this->findByJenis($jenis, $id);
        $row->status      = 'rejected';
        $row->catatan     = $r->input('catatan'); // ← SIMPAN CATATAN DI SINI
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
}
