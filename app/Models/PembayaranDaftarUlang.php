<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranDaftarUlang extends Model
{
    protected $fillable = ['user_id','foto_bukti'];
    public function user() { return $this->belongsTo(User::class); }
}

