<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $r, Closure $next, ...$roles)
    {
        abort_unless($r->user() && in_array($r->user()->role->value, $roles, true), 403, 'Forbidden');

        return $next($r);
    }
}
