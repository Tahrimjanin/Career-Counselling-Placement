<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;

class StudentVerified
{
    public function handle(Request $r, Closure $next)
    {
        abort_unless($r->user()->role !== Role::Student || $r->user()->profile?->verification_status === 'verified', 403, 'University verification is required before applying.');

        return $next($r);
    }
}
