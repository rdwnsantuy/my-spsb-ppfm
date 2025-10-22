<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // kategori_soal
        if (!Schema::hasTable('kategori_soal')) {
            Schema::create('kategori_soal', function (Blueprint $t) {
                $t->id();
                $t->string('nama_kategori');
                $t->text('deskripsi')->nullable();
                $t->boolean('aktif')->default(true);
                $t->timestamps();
            });
        } else {
            Schema::table('kategori_soal', function (Blueprint $t) {
                if (!Schema::hasColumn('kategori_soal', 'aktif')) {
                    $t->boolean('aktif')->default(true);
                }
            });
        }

        // soal
        if (!Schema::hasTable('soal')) {
            Schema::create('soal', function (Blueprint $t) {
                $t->id();
                $t->foreignId('kategori_id')->constrained('kategori_soal')->cascadeOnDelete();
                $t->text('pertanyaan');
                $t->enum('tipe', ['pg', 'isian', 'esai'])->default('pg');
                $t->json('opsi_json')->nullable(); // utk PG: [A,B,C,D]
                $t->string('kunci')->nullable();   // utk PG: "A"; utk isian: string; esai: null
                $t->unsignedSmallInteger('bobot')->default(1);
                $t->boolean('aktif')->default(true);
                $t->timestamps();
            });
        } else {
            Schema::table('soal', function (Blueprint $t) {
                if (!Schema::hasColumn('soal', 'bobot')) {
                    $t->unsignedSmallInteger('bobot')->default(1);
                }
                if (!Schema::hasColumn('soal', 'aktif')) {
                    $t->boolean('aktif')->default(true);
                }
            });
        }

        // paket_kategori (pivot bobot)
        if (!Schema::hasTable('paket_kategori')) {
            Schema::create('paket_kategori', function (Blueprint $t) {
                $t->id();
                $t->foreignId('paket_id')->constrained('paket')->cascadeOnDelete();
                $t->foreignId('kategori_id')->constrained('kategori_soal')->cascadeOnDelete();
                $t->unsignedSmallInteger('bobot_kategori')->default(0); // 0-100 per kategori
                $t->unsignedSmallInteger('ambang_kelulusan')->nullable();
                $t->timestamps();
                $t->unique(['paket_id', 'kategori_id']);
            });
        }
    }

    public function down(): void
    {
        // Hindari drop otomatis agar aman di proyek yang sudah berjalan
        // Tambahkan manual bila dibutuhkan
    }
};
