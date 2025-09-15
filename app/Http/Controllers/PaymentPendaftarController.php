<?php

namespace App\Http\Controllers;

use App\Models\PembayaranPendaftaran;
use App\Models\PembayaranDaftarUlang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentPendaftarController extends Controller
{
    /**
     * GET /pendaftar/pembayaran/{type}
     * Kita tidak punya halaman khusus; arahkan ke tempat upload pada halaman yang tepat.
     */
    public function form(string $type)
    {
        if ($type === 'pendaftaran') {
            return redirect()
                ->route('pendaftar.jadwal')
                ->with('scroll_to', 'bukti-pendaftaran');
        }

        // default: daftar_ulang
        return redirect()
            ->route('pendaftar.status')
            ->with('scroll_to', 'bukti-daftar-ulang');
    }

    /**
     * POST /pendaftar/pembayaran/{type}
     * Simpan bukti pembayaran (hanya gambar).
     */
    public function store(Request $r, string $type)
    {
        $r->validate([
            'bukti' => ['required','image','mimes:jpg,jpeg,png,webp','max:4096'],
        ]);

        $uid  = Auth::id();
        $dir  = $type === 'pendaftaran' ? 'bukti/pendaftaran' : 'bukti/daftar_ulang';
        $path = $r->file('bukti')->store($dir, 'public');

        // payload minimal (tanpa 'note' agar tidak error walau kolom 'note' belum ada)
        $payload = [
            'user_id'    => $uid,
            'foto_bukti' => $path,
            'status'     => 'pending',
            // 'verified_by' dan 'verified_at' biarkan null (kolomnya nullable)
        ];

        if ($type === 'pendaftaran') {
            PembayaranPendaftaran::create($payload);
            return back()->with('ok', 'Bukti pembayaran pendaftaran diunggah. Menunggu verifikasi admin.');
        }

        PembayaranDaftarUlang::create($payload);
        return back()->with('ok', 'Bukti pembayaran daftar ulang diunggah. Menunggu verifikasi admin.');
    }
}
