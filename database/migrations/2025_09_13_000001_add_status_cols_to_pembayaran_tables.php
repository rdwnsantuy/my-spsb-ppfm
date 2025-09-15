<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ===== pembayaran_pendaftarans
        Schema::table('pembayaran_pendaftarans', function (Blueprint $table) {
            if (!Schema::hasColumn('pembayaran_pendaftarans', 'foto_bukti')) {
                $table->string('foto_bukti', 255)->after('user_id');
            }
            if (!Schema::hasColumn('pembayaran_pendaftarans', 'status')) {
                $table->enum('status', ['pending','accepted','rejected'])
                      ->default('pending')->after('foto_bukti');
            }
            if (!Schema::hasColumn('pembayaran_pendaftarans', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('status');
                $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('pembayaran_pendaftarans', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('pembayaran_pendaftarans', 'note')) {
                $table->text('note')->nullable()->after('verified_at');
            }
        });

        // ===== pembayaran_daftar_ulangs
        Schema::table('pembayaran_daftar_ulangs', function (Blueprint $table) {
            if (!Schema::hasColumn('pembayaran_daftar_ulangs', 'foto_bukti')) {
                $table->string('foto_bukti', 255)->after('user_id');
            }
            if (!Schema::hasColumn('pembayaran_daftar_ulangs', 'status')) {
                $table->enum('status', ['pending','accepted','rejected'])
                      ->default('pending')->after('foto_bukti');
            }
            if (!Schema::hasColumn('pembayaran_daftar_ulangs', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('status');
                $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('pembayaran_daftar_ulangs', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('pembayaran_daftar_ulangs', 'note')) {
                $table->text('note')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran_pendaftarans', function (Blueprint $table) {
            if (Schema::hasColumn('pembayaran_pendaftarans', 'verified_by')) {
                $table->dropForeign(['verified_by']);
            }
            if (Schema::hasColumn('pembayaran_pendaftarans', 'note'))        $table->dropColumn('note');
            if (Schema::hasColumn('pembayaran_pendaftarans', 'verified_at')) $table->dropColumn('verified_at');
            if (Schema::hasColumn('pembayaran_pendaftarans', 'verified_by')) $table->dropColumn('verified_by');
            if (Schema::hasColumn('pembayaran_pendaftarans', 'status'))      $table->dropColumn('status');
            // (Sengaja tidak menghapus foto_bukti karena kemungkinan kolom ini sudah ada sejak awal)
        });

        Schema::table('pembayaran_daftar_ulangs', function (Blueprint $table) {
            if (Schema::hasColumn('pembayaran_daftar_ulangs', 'verified_by')) {
                $table->dropForeign(['verified_by']);
            }
            if (Schema::hasColumn('pembayaran_daftar_ulangs', 'note'))        $table->dropColumn('note');
            if (Schema::hasColumn('pembayaran_daftar_ulangs', 'verified_at')) $table->dropColumn('verified_at');
            if (Schema::hasColumn('pembayaran_daftar_ulangs', 'verified_by')) $table->dropColumn('verified_by');
            if (Schema::hasColumn('pembayaran_daftar_ulangs', 'status'))      $table->dropColumn('status');
        });
    }
};
