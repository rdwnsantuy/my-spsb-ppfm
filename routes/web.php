<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PendaftarController;
use App\Http\Controllers\PaymentPendaftarController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Root
 */
Route::get('/', fn () =>
    Auth::check() ? redirect()->route('dashboard') : redirect()->route('login')
);

/**
 * Router pusat dashboard
 */
Route::get('/dashboard', function () {
    $u = Auth::user();
    if (! $u) return redirect()->route('login');

    return $u->role === 'admin'
        ? redirect()->route('admin.dashboard')
        : redirect()->route('pendaftar.dashboard'); // akan di-redirect lagi oleh home()
})->middleware('auth')->name('dashboard');

/* ===========================
|  Pendaftar
|=========================== */
Route::middleware(['auth', 'role:pendaftar'])
    ->prefix('pendaftar')->as('pendaftar.')
    ->group(function () {

        // Halaman default pendaftar -> auto redirect ke DAFTAR (kalau belum isi) atau JADWAL (kalau sudah)
        Route::get('/', [PendaftarController::class, 'home'])->name('dashboard');

        // Hanya untuk yang BELUM isi form
        Route::middleware('form.incomplete')->group(function () {
            Route::get('/daftar',       [PendaftarController::class, 'daftarWelcome'])->name('daftar');       // lapis 1 (welcome)
            Route::get('/daftar/form',  [PendaftarController::class, 'daftarForm'])->name('daftar.form');     // lapis 2 (form)
            Route::post('/daftar/form', [PendaftarController::class, 'storeDaftarPesantren'])->name('daftar.store');
        });

        // Hanya untuk yang SUDAH isi form
        Route::middleware('form.completed')->group(function () {
            Route::get('/jadwal',                 [PendaftarController::class, 'jadwal'])->name('jadwal');
            Route::get('/data-pendaftar',         [PendaftarController::class, 'dataPendaftar'])->name('data-pendaftar');
            Route::get('/data-pendaftar/edit',    [PendaftarController::class, 'editDataPendaftar'])->name('data-pendaftar.edit');
            Route::post('/data-pendaftar/edit',   [PendaftarController::class, 'storeDaftarPesantren'])->name('data-pendaftar.update');
            Route::get('/status',                 [PendaftarController::class, 'status'])->name('status');

            // Upload bukti pembayaran oleh pendaftar (tanpa GET form, POST saja)
            Route::post('/pembayaran/{type}', [PaymentPendaftarController::class, 'store'])
                ->whereIn('type', ['pendaftaran', 'daftar_ulang'])
                ->name('payment.store'); // dipakai di blade: route('pendaftar.payment.store', 'pendaftaran' | 'daftar_ulang')
        });
    });

/* ===========================
|  Admin
|=========================== */
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')->as('admin.')
    ->group(function () {
        Route::get('/',                       [AdminController::class, 'index'])->name('dashboard');
        Route::get('/verifikasi-pembayaran',  [AdminController::class, 'verifikasiPembayaran'])->name('verifikasi-pembayaran');
        Route::get('/jadwal-seleksi',         [AdminController::class, 'jadwalSeleksi'])->name('jadwal-seleksi');
        Route::get('/data-pendaftar',         [AdminController::class, 'dataPendaftar'])->name('data-pendaftar');
        Route::get('/soal-seleksi',           [AdminController::class, 'soalSeleksi'])->name('soal-seleksi');

        // Aksi verifikasi pembayaran (tetap di halaman verifikasi pembayaran yang sudah ada)
        Route::post('/verifikasi-pembayaran/{jenis}/{id}/terima', [AdminController::class, 'terimaPembayaran'])
            ->whereIn('jenis', ['pendaftaran', 'daftar-ulang'])
            ->name('verifikasi-pembayaran.terima');

        Route::post('/verifikasi-pembayaran/{jenis}/{id}/tolak',  [AdminController::class, 'tolakPembayaran'])
            ->whereIn('jenis', ['pendaftaran', 'daftar-ulang'])
            ->name('verifikasi-pembayaran.tolak');
    });

require __DIR__ . '/auth.php';
