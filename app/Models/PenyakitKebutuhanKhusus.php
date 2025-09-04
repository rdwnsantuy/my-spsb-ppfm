<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenyakitKebutuhanKhusus extends Model
{
    protected $table = 'penyakit_kebutuhan_khususes'; // biar pasti sesuai nama tabel
    protected $fillable = ['user_id','deskripsi','tingkat'];
    public function user() { return $this->belongsTo(User::class); }
}

