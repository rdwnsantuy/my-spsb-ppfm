<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranDaftarUlang extends Model
{
    // protected $table = 'pembayaran_daftar_ulang';

    protected $fillable = [     // ← pastikan ada foto_bukti
        'user_id',
        'foto_bukti',
        'status',
        'verified_by',
        'verified_at',
        'note',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopePending($q){ return $q->where('status', 'pending'); }
    public function scopeApproved($q){ return $q->where('status', 'approved'); }
    public function scopeRejected($q){ return $q->where('status', 'rejected'); }
}
