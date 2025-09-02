<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan form register.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Proses register user baru.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255'],
            // nomor telepon 10–15 digit
            'no_telp'  => ['required', 'string', 'regex:/^\d{10,15}$/'],
            // NIK 16 digit & unik
            'nik'      => ['required', 'string', 'digits:16', Rule::unique('users', 'nik')],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'lowercase', 'min:3', 'max:30', Rule::unique('users', 'username')],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        // Simpan; (opsional) sanitasi angka:
        $noTelp = preg_replace('/\D+/', '', (string)$request->input('no_telp'));
        $nik    = preg_replace('/\D+/', '', (string)$request->input('nik'));

        $user = \App\Models\User::create([
            'name'     => $request->string('name')->toString(),
            'email'    => $request->string('email')->toString(),
            'no_telp'  => $noTelp,
            'nik'      => $nik,
            'username' => $request->string('username')->toString(),
            'password' => \Illuminate\Support\Facades\Hash::make($request->string('password')->toString()),
            'role'     => \App\Models\User::ROLE_PENDAFTAR, // default
        ]);

        event(new \Illuminate\Auth\Events\Registered($user));
        \Illuminate\Support\Facades\Auth::login($user);

        return redirect()->route('dashboard');
    }
}
