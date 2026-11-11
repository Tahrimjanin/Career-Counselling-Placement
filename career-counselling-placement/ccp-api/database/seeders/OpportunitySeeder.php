<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Opportunity;
use Illuminate\Support\Facades\DB;

class OpportunitySeeder extends DemoSeeder
{
    public function run(): void
    {
        $this->ensureDemoEnvironment();
        $co = Company::where('name', 'Demo Cedar Software')->firstOrFail();
        foreach (['Internship', 'Job', 'Training', 'Part-time'] as $type) {
            $o = Opportunity::firstOrCreate(['company_id' => $co->id, 'title' => 'Demo '.$type.' opportunity'], ['type' => $type, 'location' => 'Dhaka', 'description' => 'Fictional opportunity used to test the local platform.', 'skills' => ['React', 'Laravel'], 'deadline' => now()->addDays(30), 'status' => 'open']);
            foreach (DB::table('skills')->whereIn('name', ['React', 'Laravel'])->pluck('id') as $id) {
                DB::table('opportunity_skill')->updateOrInsert(['opportunity_id' => $o->id, 'skill_id' => $id], ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
