<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom baru (nullable agar data lama tidak rusak). Validasi register akan tetap mewajibkan.
            if (!Schema::hasColumn('users', 'nik')) {
                $table->string('nik', 32)->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'no_telp')) {
                $table->string('no_telp', 32)->nullable()->after('nik');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'no_telp')) {
                $table->dropColumn('no_telp');
            }
            if (Schema::hasColumn('users', 'nik')) {
                $table->dropColumn('nik');
            }
        });
    }
};
