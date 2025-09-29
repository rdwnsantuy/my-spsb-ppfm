<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PercobaanUjian extends Model
{
    use HasFactory;

    protected $table = 'percobaan_ujian';
    protected $guarded = [];

    protected $casts = [
        'mulai_pada'   => 'datetime',
        'selesai_pada' => 'datetime',
        'lulus'        => 'boolean',
    ];

    /* =========================
     |  Relationships
     |=========================*/
    public function paket(): BelongsTo
    {
        return $this->belongsTo(PaketUjian::class, 'paket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(JawabanUjian::class, 'percobaan_id');
    }

    public function nilaiKategori(): HasMany
    {
        return $this->hasMany(NilaiKategori::class, 'percobaan_id');
    }

    /* =========================
     |  Scopes
     |=========================*/
    /** Sedang berlangsung */
    public function scopeBerlangsung($q)
    {
        return $q->where('status', 'berlangsung');
    }

    /** Sudah selesai (manual submit) */
    public function scopeSelesai($q)
    {
        return $q->where('status', 'selesai');
    }

    /** Kadaluarsa (habis waktu) */
    public function scopeKadaluarsa($q)
    {
        return $q->where('status', 'kadaluarsa');
    }

    /** Filter milik user tertentu */
    public function scopeOfUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    /** Filter pada paket tertentu */
    public function scopeOfPaket($q, int $paketId)
    {
        return $q->where('paket_id', $paketId);
    }

    /* =========================
     |  Helpers
     |=========================*/
    /** Apakah attempt masih aktif (status berlangsung/dibuat dan belum lewat waktu) */
    public function isActive(): bool
    {
        if (! in_array($this->status, ['berlangsung', 'dibuat'], true)) {
            return false;
        }
        if ($this->selesai_pada && now()->gt($this->selesai_pada)) {
            return false;
        }
        return true;
    }

    /** Apakah attempt sudah selesai (submit) */
    public function isFinished(): bool
    {
        return $this->status === 'selesai';
    }

    /** Apakah attempt kadaluarsa (waktu habis) */
    public function isExpired(): bool
    {
        return $this->status === 'kadaluarsa';
    }

    /** Sisa detik menuju selesai (>=0). Jika sudah lewat waktu, 0. */
    public function remainingSeconds(): int
    {
        if (! $this->selesai_pada) return 0;
        $diff = $this->selesai_pada->diffInSeconds(now(), false);
        return $diff < 0 ? -$diff : 0;
    }
}
