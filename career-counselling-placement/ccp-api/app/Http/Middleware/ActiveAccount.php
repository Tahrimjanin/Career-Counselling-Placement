<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveAccount
{
    public function handle(Request $r, Closure $next)
    {
        abort_unless($r->user()->status !== 'suspended', 403, 'This account is suspended.');

        return $next($r);
    }
}
