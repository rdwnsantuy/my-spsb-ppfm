<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('jawaban_ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('percobaan_id')->constrained('percobaan_ujian')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soal')->restrictOnDelete();
            $table->longText('teks_soal_snapshot');
            $table->json('opsi_snapshot');
            $table->char('opsi_dipilih', 1)->nullable();
            $table->boolean('benar')->nullable();
            $table->decimal('skor_diperoleh', 5, 2)->default(0);
            $table->foreignId('kategori_id')->constrained('kategori_soal')->restrictOnDelete();
            $table->smallInteger('urutan_soal');
            $table->tinyInteger('bobot')->default(1);
            $table->timestamps();

            $table->index(['percobaan_id','kategori_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('jawaban_ujian');
    }
};
