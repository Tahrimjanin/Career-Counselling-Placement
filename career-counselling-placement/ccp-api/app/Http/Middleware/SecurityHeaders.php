<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($r, Closure $next)
    {
        $response = $next($r);
        foreach (['X-Content-Type-Options' => 'nosniff', 'X-Frame-Options' => 'DENY', 'Referrer-Policy' => 'strict-origin-when-cross-origin'] as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
