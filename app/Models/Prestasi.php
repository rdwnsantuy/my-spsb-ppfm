<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestasi extends Model
{
    protected $fillable = ['user_id','prestasi_i','prestasi_ii','prestasi_iii'];
    public function user() { return $this->belongsTo(User::class); }
}

