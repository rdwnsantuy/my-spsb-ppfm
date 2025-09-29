<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaketUjian extends Model
{
    use HasFactory;

    protected $table = 'paket_ujian';
    protected $guarded = [];

    protected $casts = [
        'mulai_pada' => 'datetime',
        'selesai_pada' => 'datetime',
        'acak_soal' => 'boolean',
        'acak_opsi' => 'boolean',
        'boleh_kembali' => 'boolean',
    ];

    /** Kuota & bobot per kategori untuk paket ini */
    public function kategori(): HasMany
    {
        return $this->hasMany(PaketKategori::class, 'paket_id');
    }

    /** Percobaan ujian yang memakai paket ini */
    public function percobaan(): HasMany
    {
        return $this->hasMany(PercobaanUjian::class, 'paket_id');
    }

    /** Scope paket aktif di periode waktu */
    public function scopeAktif($q)
    {
        $now = now();
        return $q->where(function ($q) use ($now) {
            $q->whereNull('mulai_pada')->orWhere('mulai_pada', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('selesai_pada')->orWhere('selesai_pada', '>=', $now);
        });
    }
}
