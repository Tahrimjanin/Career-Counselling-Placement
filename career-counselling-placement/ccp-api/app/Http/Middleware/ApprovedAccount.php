<?php

namespace App\Http\Middleware;

use Closure;

class ApprovedAccount
{
    public function handle($r, Closure $next)
    {
        abort_unless($r->user()->status === 'active', 403, 'Your account is awaiting administrator approval.');

        return $next($r);
    }
}
