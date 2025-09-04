<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wali extends Model
{
    // Penting: samakan dengan nama tabel di DB (migration kita: "walies")
    protected $table = 'walies';

    protected $fillable = [
        'user_id','nama_wali','hubungan_wali','rerata_penghasilan','no_telp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
