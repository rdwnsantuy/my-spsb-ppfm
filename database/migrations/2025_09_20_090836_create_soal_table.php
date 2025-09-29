<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori_soal')->cascadeOnDelete();
            $table->longText('teks_soal');
            $table->string('media')->nullable();
            $table->enum('tingkat_kesulitan', ['mudah','sedang','sulit'])->default('sedang');
            $table->tinyInteger('bobot')->default(1);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('soal');
    }
};
