<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriSoal extends Model
{
    use HasFactory;

    protected $table = 'kategori_soal';
    protected $guarded = [];

    /** Relasi: kategori punya banyak soal */
    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'kategori_id');
    }

    /** Relasi: pivot paket_kategori */
    public function paketKategori(): HasMany
    {
        return $this->hasMany(PaketKategori::class, 'kategori_id');
    }
}
