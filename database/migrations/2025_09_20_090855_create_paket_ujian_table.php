<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('paket_ujian', function (Blueprint $table) {
            $table->id();
            $table->string('nama_paket', 150);
            $table->text('deskripsi')->nullable();
            $table->smallInteger('durasi_menit');
            $table->timestamp('mulai_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->boolean('acak_soal')->default(true);
            $table->boolean('acak_opsi')->default(true);
            $table->boolean('boleh_kembali')->default(false);
            $table->tinyInteger('maksimal_percobaan')->default(1);
            $table->tinyInteger('ambang_kelulusan_total')->nullable();
            $table->enum('strategi_kelulusan', ['total_saja','total_dan_semua_kategori','khusus'])
                  ->default('total_saja');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('paket_ujian');
    }
};
