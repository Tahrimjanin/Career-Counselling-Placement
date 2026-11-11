<?php

namespace Database\Seeders;

use App\Models\StudentProfile;
use App\Models\User;

class RoleUserSeeder extends DemoSeeder
{
    public function run(): void
    {
        $this->ensureDemoEnvironment();
        foreach (['admin', 'advisor', 'recruiter', 'student'] as $role) {
            $u = User::firstOrCreate(['email' => $role.'@demo.test'], ['name' => 'Demo '.ucfirst($role), 'password' => 'DemoPass123!', 'role' => $role, 'email_verified_at' => now()]);
            if ($role === 'student') {
                StudentProfile::firstOrCreate(['user_id' => $u->id], ['department' => 'CSE']);
            }
        }
        foreach (['pending', 'rejected', 'unverified'] as $state) {
            $u = User::firstOrCreate(['email' => $state.'@demo.test'], ['name' => 'Demo '.ucfirst($state).' Student', 'password' => 'DemoPass123!', 'role' => 'student', 'email_verified_at' => $state === 'unverified' ? null : now()]);
            StudentProfile::firstOrCreate(['user_id' => $u->id], ['department' => 'CSE', 'verification_status' => $state === 'rejected' ? 'rejected' : 'pending', 'verification_reason' => $state === 'rejected' ? 'Academic information needs correction.' : null]);
        }
        User::firstOrCreate(['email' => 'temporary@demo.test'], ['name' => 'Demo Temporary Advisor', 'password' => 'DemoPass123!', 'role' => 'advisor', 'email_verified_at' => now(), 'must_change_password' => true]);

    }
}
