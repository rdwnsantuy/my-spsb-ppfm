<?php

namespace App\Services;

use App\Models\IzinUlangUjian;
use App\Models\PercobaanUjian;
use App\Models\PaketUjian;

class UjianQuota
{
    /**
     * Total attempt yang diizinkan: batas paket + kuota tambahan aktif dari admin.
     * - Status "aktif" dibaca case-insensitive.
     * - Jika $paket->maksimal_percobaan belum termuat, ambil dari DB.
     */
    public static function allowedAttempts(int $userId, PaketUjian $paket): int
    {
        // Pastikan base terisi
        $base = $paket->maksimal_percobaan;
        if ($base === null) {
            $base = (int) PaketUjian::where('id', $paket->id)->value('maksimal_percobaan');
        } else {
            $base = (int) $base;
        }

        // Kuota tambahan aktif (status 'aktif' / 'Aktif', dan belum kadaluarsa)
        $extra = IzinUlangUjian::query()
            ->where('user_id', $userId)
            ->where('paket_id', $paket->id)
            ->whereRaw('LOWER(status) = ?', ['aktif']) // case-insensitive
            ->where(function ($q) {
                $q->whereNull('berlaku_sampai')
                  ->orWhere('berlaku_sampai', '>=', now());
            })
            ->sum('kuota_tambahan');

        return $base + (int) $extra;
    }

    /** Attempt yang sudah dipakai user pada paket ini (semua status). */
    public static function usedAttempts(int $userId, PaketUjian $paket): int
    {
        return PercobaanUjian::query()
            ->where('user_id', $userId)
            ->where('paket_id', $paket->id)
            ->count();
    }

    /**
     * Boleh mulai attempt baru? (tak ada attempt "berlangsung/dibuat" dan kuota masih ada).
     * Sekalian berjaga: kalau ada attempt "berlangsung" tapi sudah lewat waktu, jangan block.
     */
    public static function canStartNewAttempt(int $userId, PaketUjian $paket): bool
    {
        // Ada attempt berjalan yg masih valid?
        $running = PercobaanUjian::query()
            ->where('user_id', $userId)
            ->where('paket_id', $paket->id)
            ->whereIn('status', ['dibuat','berlangsung'])
            ->where(function ($q) {
                $q->whereNull('selesai_pada')->orWhere('selesai_pada', '>', now());
            })
            ->exists();

        if ($running) return false;

        return self::usedAttempts($userId, $paket) < self::allowedAttempts($userId, $paket);
    }
}
