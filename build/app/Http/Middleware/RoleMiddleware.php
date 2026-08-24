<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!$request->user() || !$request->user()->hasRole(...$roles)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
