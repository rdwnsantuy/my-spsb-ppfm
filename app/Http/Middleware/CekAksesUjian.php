<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PembayaranPendaftaran;

class CekAksesUjian
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // pastikan sudah login
        if (! $user) {
            return redirect()->route('login');
        }

        // cek apakah user punya pembayaran pendaftaran yang accepted
        $hasValidPayment = PembayaranPendaftaran::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->exists();

        if (! $hasValidPayment) {
            return redirect()->route('dashboard')
                ->with('error', 'Akses ujian hanya untuk pendaftar dengan pembayaran pendaftaran yang sudah diterima.');
        }

        return $next($request);
    }
}
