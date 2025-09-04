<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataDiri extends Model
{
    protected $fillable = [
        'user_id','nama_lengkap','jenis_kelamin','kabupaten_lahir','tanggal_lahir',
        'foto_diri','nisn','alamat_domisili','foto_kk','no_kk'
    ];
    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }
    public function user() { return $this->belongsTo(User::class); }
}

