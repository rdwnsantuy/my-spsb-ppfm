<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PaketUjian extends Model
{
    protected $table = 'paket_ujian';

    protected $fillable = [
        'nama_paket',
        'ambang_kelulusan_total',
        'strategi_kelulusan',
        // tambahkan kolom lain yang memang ada pada tabelmu
    ];

    /** Relasi: paket ujian <-> banyak kategori via paket_kategori */
    public function kategori(): BelongsToMany
    {
        return $this->belongsToMany(
            KategoriSoal::class,
            'paket_kategori',
            'paket_id',
            'kategori_id'
        )->withPivot(['bobot_kategori', 'ambang_kelulusan'])
         ->withTimestamps();
    }
}
