<?php

use App\Http\Middleware\ActiveAccount;
use App\Http\Middleware\ApprovedAccount;
use App\Http\Middleware\EmailVerificationRequired;
use App\Http\Middleware\PasswordChanged;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StudentVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias(['approved' => ApprovedAccount::class, 'email.required' => EmailVerificationRequired::class, 'active' => ActiveAccount::class, 'password.changed' => PasswordChanged::class, 'student.verified' => StudentVerified::class, 'role' => RoleMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
