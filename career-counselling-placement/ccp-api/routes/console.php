<?php

use App\Models\Opportunity;
use App\Models\User;
use App\Services\ReminderScheduler;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\URL;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ccp:verification-link {email}', function () {
    if (! app()->environment('local') || config('mail.default') !== 'log') {
        $this->error('This helper is available only in local mode with the log mailer.');

        return 1;
    }
    $user = User::where('email', strtolower(trim($this->argument('email'))))->first();
    if (! $user) {
        $this->error('Account not found.');

        return 1;
    }
    $signed = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]);
    $this->line(rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/').'/verify-email?url='.rawurlencode($signed));
})->purpose('Print a local verification link without parsing the mail log');

Artisan::command('ccp:close-opportunities', function () {
    $count = Opportunity::where('status', 'open')->whereDate('deadline', '<', now('Asia/Dhaka')->toDateString())->update(['status' => 'closed']);
    $this->info("Closed {$count} expired opportunities.");
})->purpose('Close opportunities after their Dhaka deadline');
Schedule::command('ccp:close-opportunities')->hourly();

Artisan::command('ccp:reminders', function () {
    app(ReminderScheduler::class)->run();
    $this->info('Due reminders processed.');
});
Schedule::command('ccp:reminders')->everyMinute()->withoutOverlapping();
