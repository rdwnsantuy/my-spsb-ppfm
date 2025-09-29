<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('paket_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_id')->constrained('paket_ujian')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('kategori_soal')->restrictOnDelete();
            $table->smallInteger('jumlah_soal');
            $table->tinyInteger('bobot_kategori')->default(0); // %
            $table->tinyInteger('ambang_kelulusan')->nullable();
            $table->timestamps();

            $table->unique(['paket_id','kategori_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('paket_kategori');
    }
};
