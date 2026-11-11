<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PasswordChanged
{
    public function handle(Request $r, Closure $next)
    {
        abort_unless(! $r->user()->must_change_password, 403, 'Change your temporary password first.');

        return $next($r);
    }
}
