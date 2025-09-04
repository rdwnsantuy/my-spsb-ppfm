<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendidikanTujuan extends Model
{
    protected $fillable = ['user_id','pendidikan_tujuan'];
    public function user() { return $this->belongsTo(User::class); }
}
