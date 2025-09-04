<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformasiPsb extends Model
{
    protected $fillable = ['user_id','informasi_psb'];
    public function user() { return $this->belongsTo(User::class); }
}

