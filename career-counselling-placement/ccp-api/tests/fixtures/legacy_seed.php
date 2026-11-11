<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$now = now();
$advisor = DB::table('users')->insertGetId(['name' => 'Legacy Advisor', 'email' => 'legacy-advisor@demo.test', 'password' => bcrypt('DemoPass123!'), 'role' => 'advisor', 'created_at' => $now, 'updated_at' => $now]);
$student = DB::table('users')->insertGetId(['name' => 'Legacy Student', 'email' => 'legacy-student@demo.test', 'password' => bcrypt('DemoPass123!'), 'role' => 'student', 'advisor_id' => $advisor, 'department' => 'CSE', 'skills' => json_encode(['Python']), 'created_at' => $now, 'updated_at' => $now]);
$company = DB::table('companies')->insertGetId(['name' => 'Legacy Demo Company', 'created_at' => $now, 'updated_at' => $now]);
$o = DB::table('opportunities')->insertGetId(['company_id' => $company, 'title' => 'Legacy Job', 'type' => 'Job', 'location' => 'Demo', 'description' => 'Legacy fixture', 'created_at' => $now, 'updated_at' => $now]);
$cv = DB::table('cvs')->insertGetId(['user_id' => $student, 'version' => 1, 'path' => 'private/legacy.pdf', 'status' => 'approved', 'created_at' => $now, 'updated_at' => $now]);
DB::table('applications')->insert(['user_id' => $student, 'opportunity_id' => $o, 'cv_id' => $cv, 'admin_review' => 'approved', 'forwarded_at' => $now, 'status' => 'Shortlisted', 'created_at' => $now, 'updated_at' => $now]);
DB::table('notes')->insert(['user_id' => $advisor, 'student_id' => $student, 'body' => 'Legacy advisor note', 'private' => true, 'created_at' => $now, 'updated_at' => $now]);
