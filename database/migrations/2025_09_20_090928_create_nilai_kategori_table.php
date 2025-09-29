<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('nilai_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('percobaan_id')->constrained('percobaan_ujian')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('kategori_soal')->restrictOnDelete();
            $table->decimal('poin_diperoleh', 6, 2)->default(0);
            $table->decimal('poin_maksimal', 6, 2)->default(0);
            $table->decimal('persentase', 5, 2)->default(0);
            $table->boolean('lulus')->default(false);
            $table->timestamps();

            $table->unique(['percobaan_id','kategori_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('nilai_kategori');
    }
};
