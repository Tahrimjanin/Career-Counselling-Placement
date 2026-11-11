<?php

use App\Models\User;
use App\Services\AdvisorAllocation;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Run only against a dedicated disposable MySQL test database, after migrations.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! str_contains((string) config('database.connections.mysql.database'), 'test')) {
    throw new RuntimeException('A dedicated test database is required.');
}
$mode = $argv[1] ?? '';
if ($mode === 'init') {
    foreach (['race-admin' => 'admin', 'race-advisor' => 'advisor', 'race-one' => 'student', 'race-two' => 'student'] as $email => $role) {
        User::firstOrCreate(['email' => $email.'@demo.test'], ['name' => $email, 'role' => $role, 'password' => 'DemoPass123!', 'email_verified_at' => now()]);
    }
    $id = User::where('email', 'race-advisor@demo.test')->value('id');
    DB::table('advisor_profiles')->updateOrInsert(['user_id' => $id], ['capacity' => 1, 'is_accepting' => true, 'created_at' => now(), 'updated_at' => now()]);
    echo "initialized\n";
} elseif ($mode === 'check') {
    $id = User::where('email', 'race-advisor@demo.test')->value('id');
    if (DB::table('advisor_assignments')->where('advisor_id', $id)->where('status', 'active')->count() !== 1) {
        throw new RuntimeException('Capacity race failed');
    }echo "PASS: concurrent allocation respects capacity 1\n";
} else {
    try {
        (new AdvisorAllocation)->assign(User::where('email', $mode.'@demo.test')->value('id'), User::where('email', 'race-advisor@demo.test')->value('id'), User::where('email', 'race-admin@demo.test')->value('id'));
        echo "assigned\n";
    } catch (HttpException $e) {
        if ($e->getStatusCode() !== 422) {
            throw $e;
        }echo "capacity protected\n";
    }
}
