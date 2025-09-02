<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PendaftarController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth; // <-- penting

/**
 * Root: kalau belum login → /login
 * kalau sudah login → /dashboard (yang redirect ke admin/pendaftar)
 */
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/**
 * Central "dashboard"
 * Semua redirect (login/register/komponen Breeze) diarahkan ke sini,
 * lalu diarahkan lagi sesuai role.
 */
Route::get('/dashboard', function () {
    $user = Auth::user();
    if (!$user) return redirect()->route('login');

    return $user->role === 'admin'
        ? redirect()->route('admin.dashboard')
        : redirect()->route('pendaftar.dashboard');
})->middleware(['auth'])->name('dashboard');

/** Halaman Pendaftar (role: pendaftar) */
Route::get('/pendaftar', [PendaftarController::class, 'index'])
    ->middleware(['auth', 'role:pendaftar'])
    ->name('pendaftar.dashboard');

/** Halaman Admin (role: admin) */
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

/** Profile (untuk semua user yang login) */
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
