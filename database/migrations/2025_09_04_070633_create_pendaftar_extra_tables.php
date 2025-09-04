<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) DATA WALI (1–N per user)
        Schema::create('walies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('nama_wali');
            $t->enum('hubungan_wali', ['ayah','ibu','wali','lainnya']);
            $t->unsignedInteger('rerata_penghasilan')->nullable(); // rupiah/bln
            $t->string('no_telp', 20)->nullable();
            $t->timestamps();

            // cegah duplikasi jenis hubungan pada user yang sama (opsional tapi disarankan)
            $t->unique(['user_id','hubungan_wali']);
        });

        // 2) DATA DIRI (1–1 per user)
        Schema::create('data_diris', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->unique()->cascadeOnDelete();
            $t->string('nama_lengkap');
            $t->enum('jenis_kelamin', ['L','P']);
            $t->string('kabupaten_lahir', 100);
            $t->date('tanggal_lahir');
            $t->string('foto_diri')->nullable();     // simpan path file
            $t->string('nisn', 20)->nullable()->unique(); // boleh null, jika diisi unik
            $t->string('alamat_domisili', 255);
            $t->string('foto_kk')->nullable();      // simpan path file
            $t->string('no_kk', 32)->nullable();
            $t->timestamps();
        });

        // 3) DATA PENDIDIKAN TUJUAN (1–1 per user)
        Schema::create('pendidikan_tujuans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->unique()->cascadeOnDelete();
            $t->enum('pendidikan_tujuan', ['SMP','SMA','MA','SMK','Lainnya']);
            $t->timestamps();
        });

        // 4) DATA PEMBAYARAN DAFTAR ULANG (1–N per user)
        Schema::create('pembayaran_daftar_ulangs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('foto_bukti'); // path file
            $t->timestamps();
        });

        // 5) DATA PEMBAYARAN PENDAFTARAN (1–N per user)
        Schema::create('pembayaran_pendaftarans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('foto_bukti'); // path file
            $t->timestamps();
        });

        // 6) DATA INFORMASI PSB (1–N atau 1–1; di sini fleksibel 1–N)
        Schema::create('informasi_psbs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->enum('informasi_psb', ['facebook','instagram','tiktok','website','teman','brosur','lainnya']);
            $t->timestamps();
        });

        // 7) DATA PRESTASI (bentuk yang diminta: i, ii, iii dalam 1 baris)
        Schema::create('prestasis', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('prestasi_i')->nullable();
            $t->string('prestasi_ii')->nullable();
            $t->string('prestasi_iii')->nullable();
            $t->timestamps();
        });

        // 8) DATA PENYAKIT & KEBUTUHAN KHUSUS (1–N per user)
        Schema::create('penyakit_kebutuhan_khususes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('deskripsi'); // penjelasan penyakit/kebutuhan
            $t->enum('tingkat', ['ringan','sedang','berat'])->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('penyakit_kebutuhan_khususes');
        Schema::dropIfExists('prestasis');
        Schema::dropIfExists('informasi_psbs');
        Schema::dropIfExists('pembayaran_pendaftarans');
        Schema::dropIfExists('pembayaran_daftar_ulangs');
        Schema::dropIfExists('pendidikan_tujuans');
        Schema::dropIfExists('data_diris');
        Schema::dropIfExists('walies');
        Schema::enableForeignKeyConstraints();
    }
};
