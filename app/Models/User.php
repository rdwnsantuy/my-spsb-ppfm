<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_PENDAFTAR = 'pendaftar';

    protected $fillable = [
        'name',
        'username',
        'email',
        'no_telp',   // <— baru
        'nik',       // <— baru
        'password',
        'role',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime'];
    }

    // simpan username selalu lowercase
    public function username(): Attribute
    {
        return Attribute::make(
            set: fn($value) => is_string($value) ? strtolower($value) : $value
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
    public function isPendaftar(): bool
    {
        return $this->role === self::ROLE_PENDAFTAR;
    }
}
