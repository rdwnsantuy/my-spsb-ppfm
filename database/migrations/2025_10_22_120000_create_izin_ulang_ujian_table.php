<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('izin_ulang_ujian', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('paket_id')->constrained('paket_ujian'); // tabelmu bernama paket_ujian
            $t->unsignedTinyInteger('kuota_tambahan')->default(0);
            $t->timestamp('berlaku_sampai')->nullable();
            $t->enum('status', ['aktif','nonaktif'])->default('aktif');
            $t->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('alasan')->nullable();
            $t->timestamps();

            // Satu izin aktif per user+paket (boleh punya riwayat nonaktif)
            $t->unique(['user_id','paket_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_ulang_ujian');
    }
};
