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

// ===== (Opsional) Manajemen ujian admin =====
use App\Http\Controllers\Admin\KategoriSoalController;
use App\Http\Controllers\Admin\SoalController;
use App\Http\Controllers\Admin\PaketUjianController;
use App\Http\Controllers\Admin\MonitoringUjianController;
use App\Http\Controllers\Admin\RekapUjianController;

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

                    // Hasil ujian (letakkan SEBELUM rute dinamis lainnya untuk aman)
                    Route::get('/hasil/{percobaan}', [HasilUjianController::class, 'show'])
                        ->whereNumber('percobaan')
                        ->name('result');

                    // Tampilkan soal ke-N
                    Route::get('/{percobaan}/{urutan}', [PengerjaanUjianController::class, 'show'])
                        ->whereNumber(['percobaan', 'urutan'])
                        ->name('show');

                    // Simpan jawaban 1 soal (AJAX / non-AJAX)
                    Route::post('/{percobaan}/simpan', [PengerjaanUjianController::class, 'saveAnswer'])
                        ->whereNumber('percobaan')
                        ->name('save'); // route('pendaftar.ujian.save', $attempt->id)

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
        Route::get('/soal-seleksi',          [AdminController::class, 'soalSeleksi'])->name('soal-seleksi');

        // Aksi verifikasi pembayaran (terima/tolak)
        // Terima/tolak BOTH: 'daftar-ulang' & 'daftar_ulang'
        Route::post('/verifikasi-pembayaran/{jenis}/{id}/terima', [AdminController::class, 'terimaPembayaran'])
            ->where(['jenis' => 'pendaftaran|daftar-ulang|daftar_ulang', 'id' => '[0-9]+'])
            ->name('verifikasi-pembayaran.terima');

        Route::post('/verifikasi-pembayaran/{jenis}/{id}/tolak',  [AdminController::class, 'tolakPembayaran'])
            ->where(['jenis' => 'pendaftaran|daftar-ulang|daftar_ulang', 'id' => '[0-9]+'])
            ->name('verifikasi-pembayaran.tolak');

        // ===== CRUD & Monitoring Ujian (opsional aktifkan) =====
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
    });

require __DIR__.'/auth.php';
