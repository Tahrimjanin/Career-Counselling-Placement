<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$u = DB::table('users')->where('email', 'legacy-student@demo.test')->first();
$checks = [DB::table('student_profiles')->where('user_id', $u->id)->value('department') === 'CSE', DB::table('advisor_assignments')->where('student_id', $u->id)->where('status', 'active')->count() === 1, DB::table('student_skill')->where('user_id', $u->id)->count() === 1, DB::table('applications')->where('student_id', $u->id)->value('workflow_status') === 'shortlisted', DB::table('cvs')->where('user_id', $u->id)->value('file_path') === 'private/legacy.pdf', DB::table('advisor_notes')->count() === 1];
if (in_array(false, $checks, true)) {
    throw new RuntimeException('Legacy conversion check failed');
}echo 'LEGACY CONVERSION: 6 checks passed'.PHP_EOL;
