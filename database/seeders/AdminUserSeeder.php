<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL', 'admin@example.com');
        $username = env('ADMIN_USERNAME', 'admin');
        $name     = env('ADMIN_NAME', 'Administrator');
        $password = env('ADMIN_PASSWORD', 'secret123'); // ganti di .env produksi

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'username' => $username,
                'password' => Hash::make($password),
                'role'     => User::ROLE_ADMIN,
            ]
        );

        if ($user->role !== User::ROLE_ADMIN) {
            $user->role = User::ROLE_ADMIN;
            $user->save();
        }
    }
}
