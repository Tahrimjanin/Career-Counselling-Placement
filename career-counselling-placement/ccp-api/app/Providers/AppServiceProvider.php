<?php

namespace App\Providers;

use App\Policies\WorkflowPolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('workflow', [WorkflowPolicy::class, 'perform']);
        Gate::define('student-record', [WorkspacePolicy::class, 'student']);
        Gate::define('candidate-record', [WorkspacePolicy::class, 'candidate']);
        Model::preventLazyLoading(! $this->app->isProduction());
        RateLimiter::for('login', fn ($r) => Limit::perMinute(5)->by(strtolower(is_string($r->input('email')) ? $r->input('email') : 'invalid').'|'.$r->ip()));
        RateLimiter::for('auth-mail', fn ($r) => Limit::perMinute(3)->by($r->user()?->id ?? $r->ip()));
        RateLimiter::for('upload', fn ($r) => Limit::perMinute(10)->by($r->user()?->id ?? $r->ip()));
        RateLimiter::for('api', fn ($r) => Limit::perMinute(120)->by($r->user()?->id ?? $r->ip()));
        VerifyEmail::createUrlUsing(function ($u) {
            $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $u->id, 'hash' => sha1($u->getEmailForVerification())]);

            return rtrim(config('app.frontend_url'), '/').'/verify-email?url='.rawurlencode($url);
        });
        ResetPassword::createUrlUsing(fn ($u, $token) => rtrim(config('app.frontend_url'), '/').'/reset-password?token='.$token.'&email='.rawurlencode($u->email));
    }
}
