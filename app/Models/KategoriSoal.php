<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KategoriSoal extends Model
{
    use HasFactory;

    protected $table = 'kategori_soal';

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    /** Relasi: kategori punya banyak soal */
    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'kategori_id');
    }

    /** Relasi pivot langsung (kompatibilitas lama) */
    public function paketKategori(): HasMany
    {
        return $this->hasMany(PaketKategori::class, 'kategori_id');
    }

    /** Relasi: kategori <-> banyak paket ujian via paket_kategori */
    public function paket(): BelongsToMany
    {
        return $this->belongsToMany(
            PaketUjian::class,
            'paket_kategori',
            'kategori_id',
            'paket_id'
        )->withPivot(['bobot_kategori', 'ambang_kelulusan'])
         ->withTimestamps();
    }

    /** Scope kecil */
    public function scopeAktif($q){ return $q->where('aktif', true); }
    public function scopeOrderNama($q){ return $q->orderBy('nama_kategori'); }
}
