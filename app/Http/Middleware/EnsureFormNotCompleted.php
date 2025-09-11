<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFormNotCompleted
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u && $u->hasCompletedForm()) {
            return redirect()->route('pendaftar.jadwal');
        }
        return $next($request);
    }
}
