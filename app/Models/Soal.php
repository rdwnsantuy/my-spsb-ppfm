<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Soal extends Model
{
    use HasFactory;

    /**
     * Nama tabel eksplisit (sesuai skema).
     */
    protected $table = 'soal';

    /**
     * Kolom yang boleh diisi mass-assignment.
     */
    protected $fillable = [
        'kategori_id',
        'pertanyaan',
        'tipe',        // 'pg' | 'isian' | 'esai'
        'opsi_json',   // array opsi utk PG
        'kunci',       // kunci PG ('A'..'D') / isian (string) / null utk esai
        'bobot',
        'aktif',
    ];

    /**
     * Casting atribut.
     */
    protected $casts = [
        'opsi_json' => 'array',
        'aktif'     => 'boolean',
    ];

    /**
     * Konstanta tipe soal.
     */
    public const TIPE_PG    = 'pg';
    public const TIPE_ISIAN = 'isian';
    public const TIPE_ESAI  = 'esai';

    /**
     * Relasi: kategori pemilik soal.
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSoal::class, 'kategori_id');
    }

    /**
     * Helper: apakah tipe PG / Isian / Esai.
     */
    public function isPilihanGanda(): bool
    {
        return $this->tipe === self::TIPE_PG;
    }

    public function isIsian(): bool
    {
        return $this->tipe === self::TIPE_ISIAN;
    }

    public function isEsai(): bool
    {
        return $this->tipe === self::TIPE_ESAI;
    }

    /**
     * Helper: ambil opsi (selalu array).
     */
    public function opsi(): array
    {
        return $this->opsi_json ?? [];
    }

    /**
     * Scope: hanya soal aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope: urut terbaru.
     */
    public function scopeTerbaru($query)
    {
        return $query->latest();
    }

    /**
     * Mutator kecil: rapikan pertanyaan saat diset.
     */
    public function setPertanyaanAttribute($value): void
    {
        $this->attributes['pertanyaan'] = is_string($value)
            ? trim(preg_replace('/\\s+/', ' ', $value))
            : $value;
    }
}
