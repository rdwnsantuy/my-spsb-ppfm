<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IzinUlangUjian extends Model
{
    use HasFactory;

    protected $table = 'izin_ulang_ujian';
    protected $guarded = [];
    protected $casts = ['berlaku_sampai' => 'datetime'];

    public function user()  { return $this->belongsTo(User::class); }
    public function paket() { return $this->belongsTo(PaketUjian::class, 'paket_id'); }
}
