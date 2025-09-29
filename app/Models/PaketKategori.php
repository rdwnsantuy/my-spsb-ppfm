<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaketKategori extends Model
{
    use HasFactory;

    protected $table = 'paket_kategori';
    protected $guarded = [];

    public function paket(): BelongsTo
    {
        return $this->belongsTo(PaketUjian::class, 'paket_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSoal::class, 'kategori_id');
    }
}
