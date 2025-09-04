<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PendaftarController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Root:
 * - belum login  -> /login
 * - sudah login  -> /dashboard (router pusat)
 */
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/**
 * Router pusat "dashboard" -> arahkan sesuai role
 */
Route::get('/dashboard', function () {
    $user = Auth::user();
    if (! $user) {
        return redirect()->route('login');
    }

    return $user->role === 'admin'
        ? redirect()->route('admin.dashboard')
        : redirect()->route('pendaftar.dashboard');
})->middleware('auth')->name('dashboard');

/* ===========================
|  Pendaftar
|=========================== */
Route::middleware(['auth', 'role:pendaftar'])
    ->prefix('pendaftar')->as('pendaftar.')
    ->group(function () {
        Route::get('/',                 [PendaftarController::class, 'index'])->name('dashboard');
        Route::get('/jadwal',           [PendaftarController::class, 'jadwal'])->name('jadwal');
        Route::get('/status',           [PendaftarController::class, 'status'])->name('status');
        Route::get('/daftar-pesantren', [PendaftarController::class, 'daftarPesantren'])->name('daftar-pesantren');
        Route::get('/data-pendaftar',   [PendaftarController::class, 'dataPendaftar'])->name('data-pendaftar');

        Route::post('/daftar-pesantren', [PendaftarController::class, 'storeDaftarPesantren'])->name('daftar-pesantren.store');
    });

/* ===========================
|  Admin
|=========================== */
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')->as('admin.')
    ->group(function () {
        Route::get('/',                    [AdminController::class, 'index'])->name('dashboard');
        Route::get('/verifikasi-pembayaran',[AdminController::class, 'verifikasiPembayaran'])->name('verifikasi-pembayaran');
        Route::get('/jadwal-seleksi',      [AdminController::class, 'jadwalSeleksi'])->name('jadwal-seleksi');
        Route::get('/data-pendaftar',      [AdminController::class, 'dataPendaftar'])->name('data-pendaftar');
        Route::get('/soal-seleksi',        [AdminController::class, 'soalSeleksi'])->name('soal-seleksi');
    });

require __DIR__.'/auth.php';
