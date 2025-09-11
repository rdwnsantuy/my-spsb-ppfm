<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFormCompleted
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if (! $u || ! $u->hasCompletedForm()) {
            return redirect()->route('pendaftar.daftar')
                ->with('warn', 'Silakan lengkapi formulir pendaftaran terlebih dahulu.');
        }
        return $next($request);
    }
}
