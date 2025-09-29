<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('percobaan_ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_id')->constrained('paket_ujian')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['dibuat','berlangsung','selesai','kadaluarsa'])->default('dibuat');
            $table->timestamp('mulai_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->decimal('skor_total', 5, 2)->nullable();
            $table->boolean('lulus')->nullable();
            $table->smallInteger('focus_out_count')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id','paket_id','status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('percobaan_ujian');
    }
};
