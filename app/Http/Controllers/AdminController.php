<?php

namespace App\Http\Controllers;

use App\Models\PembayaranPendaftaran;
use App\Models\PembayaranDaftarUlang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index');
    }

    /**
     * Halaman verifikasi pembayaran (gabungan: pendaftaran + daftar ulang).
     * View yang dipakai: resources/views/admin/verifikasi-pembayaran.blade.php
     */
    public function verifikasiPembayaran(): View
    {
        // Ambil dua sumber, bentukkan struktur seragam untuk dipakai di view
        $pendaftaran = PembayaranPendaftaran::with(['user','verifier'])
            ->latest()
            ->get()
            ->map(function ($r) {
                return (object) [
                    'id'          => $r->id,
                    'jenis'       => 'pendaftaran',
                    'user'        => $r->user,
                    'foto_bukti'  => $r->foto_bukti,
                    'status'      => $r->status,        // pending | accepted | rejected
                    'verified_by' => $r->verifier?->name,
                    'verified_at' => $r->verified_at,
                    'catatan'     => $r->catatan,
                    'created_at'  => $r->created_at,
                ];
            });

        $daftarUlang = PembayaranDaftarUlang::with(['user','verifier'])
            ->latest()
            ->get()
            ->map(function ($r) {
                return (object) [
                    'id'          => $r->id,
                    'jenis'       => 'daftar-ulang',
                    'user'        => $r->user,
                    'foto_bukti'  => $r->foto_bukti,
                    'status'      => $r->status,        // pending | accepted | rejected
                    'verified_by' => $r->verifier?->name,
                    'verified_at' => $r->verified_at,
                    'catatan'     => $r->catatan,
                    'created_at'  => $r->created_at,
                ];
            });

        // Gabungkan dan urutkan terbaru di atas
        $items = $pendaftaran->concat($daftarUlang)->sortByDesc('created_at')->values();

        return view('admin.verifikasi-pembayaran', compact('items'));
    }

    /**
     * Terima pembayaran (ubah status -> accepted).
     * Route: admin.verifikasi-pembayaran.terima
     */
    public function terimaPembayaran(Request $request, string $jenis, int $id): RedirectResponse
    {
        $row = $this->findPembayaran($jenis, $id);

        $row->update([
            'status'      => 'accepted',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'catatan'     => $request->input('catatan'),
        ]);

        return back()->with('ok', 'Pembayaran telah diterima.');
    }

    /**
     * Tolak pembayaran (ubah status -> rejected, catatan wajib).
     * Route: admin.verifikasi-pembayaran.tolak
     */
    public function tolakPembayaran(Request $request, string $jenis, int $id): RedirectResponse
    {
        $request->validate([
            'catatan' => ['required','string','max:500'],
        ]);

        $row = $this->findPembayaran($jenis, $id);

        $row->update([
            'status'      => 'rejected',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'catatan'     => $request->input('catatan'),
        ]);

        return back()->with('ok', 'Pembayaran ditolak.');
    }

    /**
     * Helper: cari model pembayaran sesuai jenis.
     */
    private function findPembayaran(string $jenis, int $id)
    {
        return match ($jenis) {
            'pendaftaran'  => PembayaranPendaftaran::findOrFail($id),
            'daftar-ulang' => PembayaranDaftarUlang::findOrFail($id),
            default        => abort(404),
        };
    }

    // ===== Halaman admin lainnya tetap seperti sebelumnya =====

    public function jadwalSeleksi(): View
    {
        return view('admin.jadwal-seleksi');
    }

    public function dataPendaftar(): View
    {
        return view('admin.data-pendaftar');
    }

    public function soalSeleksi(): View
    {
        return view('admin.soal-seleksi');
    }
}
