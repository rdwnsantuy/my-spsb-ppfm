<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Pakai: ->middleware('role:admin') atau ->middleware('role:pendaftar')
     * Bisa multi: ->middleware('role:admin,pendaftar')
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $allowed = array_map('trim', explode(',', $roles));
        if (!in_array($user->role, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
