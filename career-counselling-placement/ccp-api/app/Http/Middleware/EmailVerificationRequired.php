<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmailVerificationRequired
{
    public function handle(Request $request, Closure $next)
    {
        abort_if(config('ccp.require_email_verification') && ! $request->user()->hasVerifiedEmail(), 403, 'Please verify your email address.');

        return $next($request);
    }
}
