<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranPendaftaran extends Model
{
    // default table name sudah cocok: pembayaran_pendaftarans

    protected $fillable = [
        'user_id',
        'foto_bukti',
        'status',       // 'pending' | 'accepted' | 'rejected'
        'verified_by',
        'verified_at',
        'note',         // <- kolom catatan sesuai DB
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /* ========= Relasi ========= */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /* ========= Scopes (konsisten dgn enum) ========= */
    public function scopePending($q)  { return $q->where('status', 'pending'); }
    public function scopeAccepted($q) { return $q->where('status', 'accepted'); }
    public function scopeRejected($q) { return $q->where('status', 'rejected'); }
}
