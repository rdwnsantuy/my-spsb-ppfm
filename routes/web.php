<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PendaftarController;
use App\Http\Controllers\PaymentPendaftarController;

// ===== Ujian (pendaftar) =====
use App\Http\Controllers\Ujian\DaftarUjianController;
use App\Http\Controllers\Ujian\PengerjaanUjianController;
use App\Http\Controllers\Ujian\HasilUjianController;

// ===== (Opsional) Manajemen ujian admin (tetap dipertahankan)
use App\Http\Controllers\Admin\KategoriSoalController;
use App\Http\Controllers\Admin\SoalController;
use App\Http\Controllers\Admin\PaketUjianController;
use App\Http\Controllers\Admin\MonitoringUjianController;
use App\Http\Controllers\Admin\RekapUjianController;

// ===== Halaman ringkas Soal Seleksi (lama) -> dialihkan ke halaman baru
use App\Http\Controllers\Admin\Ujian\SoalSeleksiController;

// ===== Halaman baru (sesuai konsep) Bank Soal & Bobot kategori (Single Page)
use App\Http\Controllers\Admin\SoalAdminController;

// ===== Izin Ulang Ujian (BARU)
use App\Http\Controllers\Admin\IzinUlangController;

/**
 * Root
 */
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/**
 * Router pusat dashboard (arahkan sesuai role)
 */
Route::get('/dashboard', function () {
    $u = Auth::user();
    if (!$u) return redirect()->route('login');

    return $u->role === 'admin'
        ? redirect()->route('admin.dashboard')
        : redirect()->route('pendaftar.dashboard'); // pendaftar -> home() redirect lagi
})->middleware('auth')->name('dashboard');

/* ===========================
|  Pendaftar
|=========================== */
Route::middleware(['auth', 'role:pendaftar'])
    ->prefix('pendaftar')->as('pendaftar.')
    ->group(function () {

        // Halaman default pendaftar -> auto redirect ke DAFTAR (belum isi) / JADWAL (sudah)
        Route::get('/', [PendaftarController::class, 'home'])->name('dashboard');

        // --- Belum isi form
        Route::middleware('form.incomplete')->group(function () {
            Route::get('/daftar',       [PendaftarController::class, 'daftarWelcome'])->name('daftar');        // lapis 1
            Route::get('/daftar/form',  [PendaftarController::class, 'daftarForm'])->name('daftar.form');      // lapis 2
            Route::post('/daftar/form', [PendaftarController::class, 'storeDaftarPesantren'])->name('daftar.store');
        });

        // --- Sudah isi form
        Route::middleware('form.completed')->group(function () {
            Route::get('/jadwal',               [PendaftarController::class, 'jadwal'])->name('jadwal');
            Route::get('/data-pendaftar',       [PendaftarController::class, 'dataPendaftar'])->name('data-pendaftar');
            Route::get('/data-pendaftar/edit',  [PendaftarController::class, 'editDataPendaftar'])->name('data-pendaftar.edit');
            Route::post('/data-pendaftar/edit', [PendaftarController::class, 'storeDaftarPesantren'])->name('data-pendaftar.update');
            Route::get('/status',               [PendaftarController::class, 'status'])->name('status');

            // Upload bukti pembayaran oleh pendaftar
            Route::post('/pembayaran/{type}', [PaymentPendaftarController::class, 'store'])
                ->whereIn('type', ['pendaftaran', 'daftar_ulang'])
                ->name('payment.store'); // dipakai di Blade: route('pendaftar.payment.store', 'pendaftaran'|'daftar_ulang')

            // ===== UJIAN (PENDAFTAR) =====
            Route::prefix('ujian')->as('ujian.')
                ->middleware(\App\Http\Middleware\CekAksesUjian::class)
                ->group(function () {

                    // Daftar paket ujian + riwayat
                    Route::get('/',        [DaftarUjianController::class, 'index'])->name('index');
                    Route::get('/riwayat', [DaftarUjianController::class, 'history'])->name('riwayat');

                    // Mulai ujian -> generate percobaan + snapshot soal
                    Route::get('/{paket}/mulai', [PengerjaanUjianController::class, 'start'])
                        ->whereNumber('paket')
                        ->name('start');

                    // Hasil ujian
                    Route::get('/hasil/{percobaan}', [HasilUjianController::class, 'show'])
                        ->whereNumber('percobaan')
                        ->name('result');

                    // Tampilkan soal ke-N
                    Route::get('/{percobaan}/{urutan}', [PengerjaanUjianController::class, 'show'])
                        ->whereNumber(['percobaan', 'urutan'])
                        ->name('show');

                    // Simpan jawaban 1 soal
                    Route::post('/{percobaan}/simpan', [PengerjaanUjianController::class, 'saveAnswer'])
                        ->whereNumber('percobaan')
                        ->name('save');

                    // Kumpulkan jawaban (submit semua)
                    Route::post('/{percobaan}/kumpulkan', [PengerjaanUjianController::class, 'submit'])
                        ->whereNumber('percobaan')
                        ->name('submit');
                });
        });
    });

/* ===========================
|  Admin
|=========================== */
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')->as('admin.')
    ->group(function () {

        // Dashboard + halaman daftar
        Route::get('/',                      [AdminController::class, 'index'])->name('dashboard');
        Route::get('/verifikasi-pembayaran', [AdminController::class, 'verifikasiPembayaran'])->name('verifikasi-pembayaran');
        Route::get('/jadwal-seleksi',        [AdminController::class, 'jadwalSeleksi'])->name('jadwal-seleksi');
        Route::get('/data-pendaftar',        [AdminController::class, 'dataPendaftar'])->name('data-pendaftar');

        /**
         * ===== Halaman baru: Bank Soal & Bobot (single page bertab)
         * URL: /admin/soal
         * Nama route: admin.soal.*
         */
        Route::prefix('soal')->as('soal.')->group(function () {
            Route::get('/', [SoalAdminController::class, 'index'])->name('index');

            // Kategori
            Route::post('/kategori',              [SoalAdminController::class, 'storeCategory'])->name('kategori.store');
            Route::put('/kategori/{kategori}',    [SoalAdminController::class, 'updateCategory'])->name('kategori.update');
            Route::delete('/kategori/{kategori}', [SoalAdminController::class, 'destroyCategory'])->name('kategori.destroy');

            // Soal
            Route::post('/item',              [SoalAdminController::class, 'storeQuestion'])->name('item.store');
            Route::put('/item/{soal}',        [SoalAdminController::class, 'updateQuestion'])->name('item.update');
            Route::delete('/item/{soal}',     [SoalAdminController::class, 'destroyQuestion'])->name('item.destroy');

            // Bobot kategori per paket
            Route::post('/bobot',             [SoalAdminController::class, 'storeWeight'])->name('bobot.store');
            Route::put('/bobot/{pivot}',      [SoalAdminController::class, 'updateWeight'])->name('bobot.update');
            Route::delete('/bobot/{pivot}',   [SoalAdminController::class, 'destroyWeight'])->name('bobot.destroy');
        });

        /**
         * ===== RUTE LAMA (Soal Seleksi single page) — dialihkan ke halaman baru
         * Agar tautan lama tidak rusak dan nama route lama tetap bisa dipakai.
         */
        Route::get('/soal-seleksi', fn () => redirect()->route('admin.soal.index'))
            ->name('soal-seleksi');

        // Quick CRUD lama -> arahkan ke controller baru (kompatibel dengan form lama jika masih dipakai)
        Route::post('/soal-seleksi/kategori',              [SoalAdminController::class, 'storeCategory'])->name('ujian.kategori-soal.store.quick');
        Route::put('/soal-seleksi/kategori/{kategori}',    [SoalAdminController::class, 'updateCategory'])->name('ujian.kategori-soal.update.quick');
        Route::delete('/soal-seleksi/kategori/{kategori}', [SoalAdminController::class, 'destroyCategory'])->name('ujian.kategori-soal.destroy.quick');

        Route::post('/soal-seleksi/soal',              [SoalAdminController::class, 'storeQuestion'])->name('ujian.soal.store.quick');
        Route::put('/soal-seleksi/soal/{soal}',        [SoalAdminController::class, 'updateQuestion'])->name('ujian.soal.update.quick');
        Route::delete('/soal-seleksi/soal/{soal}',     [SoalAdminController::class, 'destroyQuestion'])->name('ujian.soal.destroy.quick');

        /**
         * ===== CRUD & Monitoring Ujian (halaman penuh – tetap tersedia)
         */
        Route::prefix('ujian')->as('ujian.')->group(function () {
            Route::resource('kategori-soal', KategoriSoalController::class)->parameters([
                'kategori-soal' => 'kategori'
            ]);
            Route::resource('soal',  SoalController::class);
            Route::resource('paket', PaketUjianController::class);

            Route::get('monitoring', [MonitoringUjianController::class, 'index'])->name('monitoring.index');
            Route::post('monitoring/{id}/force-submit', [MonitoringUjianController::class, 'forceSubmit'])
                ->whereNumber('id')->name('monitoring.force');

            Route::get('rekap', [RekapUjianController::class, 'index'])->name('rekap.index');
            Route::get('rekap/export', [RekapUjianController::class, 'exportCsv'])->name('rekap.export');
        });

        /**
         * ===== Izin Ulang Ujian (BARU)
         * URL: /admin/izin-ulang
         * Nama route: admin.izinulang.*
         */
        Route::prefix('izin-ulang')->as('izinulang.')->group(function () {
            Route::get('/', [IzinUlangController::class, 'index'])->name('index');
            Route::post('/', [IzinUlangController::class, 'store'])->name('store');
            Route::patch('/{izin}/nonaktif', [IzinUlangController::class, 'nonaktif'])
                ->whereNumber('izin')->name('nonaktif');
        });
    });

require __DIR__ . '/auth.php';
