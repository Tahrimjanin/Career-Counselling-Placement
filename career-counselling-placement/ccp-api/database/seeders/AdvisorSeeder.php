<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdvisorSeeder extends DemoSeeder
{
    public function run(): void
    {
        $this->ensureDemoEnvironment();
        foreach (User::where('role', 'advisor')->get() as $u) {
            DB::table('advisor_profiles')->updateOrInsert(['user_id' => $u->id], ['title' => 'Career Advisor', 'bio' => 'Fictional advisor for local demonstrations.', 'capacity' => 20, 'is_accepting' => true, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('advisor_expertise')->updateOrInsert(['advisor_id' => $u->id, 'tag' => 'Software engineering'], ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
