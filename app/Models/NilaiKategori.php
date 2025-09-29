<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiKategori extends Model
{
    use HasFactory;

    protected $table = 'nilai_kategori';
    protected $guarded = [];

    // Pastikan kolom numerik diperlakukan sebagai float, lulus sebagai boolean
    protected $casts = [
        'poin_diperoleh' => 'float',
        'poin_maksimal'  => 'float',
        'persentase'     => 'float',
        'lulus'          => 'boolean',
    ];

    /* =========================
     |  Relationships
     |=========================*/
    public function percobaan(): BelongsTo
    {
        return $this->belongsTo(PercobaanUjian::class, 'percobaan_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSoal::class, 'kategori_id');
    }

    /* =========================
     |  Scopes (opsional, memudahkan query)
     |=========================*/
    public function scopeOfPercobaan($q, int $percobaanId)
    {
        return $q->where('percobaan_id', $percobaanId);
    }

    public function scopeOfKategori($q, int $kategoriId)
    {
        return $q->where('kategori_id', $kategoriId);
    }

    /* =========================
     |  Helpers (opsional)
     |=========================*/

    /** Persentase dalam format string, mis. "83.5%" */
    public function getPersentaseFormattedAttribute(): string
    {
        $v = is_numeric($this->persentase) ? round($this->persentase, 2) : 0;
        return $v.'%';
    }
}
