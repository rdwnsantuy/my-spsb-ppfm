<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Soal extends Model
{
    use HasFactory;

    protected $table = 'soal';
    protected $guarded = [];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];

    /** Kategori pemilik soal */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSoal::class, 'kategori_id');
    }

    /** Opsi jawaban milik soal */
    public function opsi(): HasMany
    {
        return $this->hasMany(OpsiJawaban::class, 'soal_id');
    }
}
