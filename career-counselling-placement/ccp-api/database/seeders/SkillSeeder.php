<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['technical' => ['React', 'Laravel', 'Python', 'SQL'], 'soft' => ['Communication', 'Teamwork', 'Problem solving']] as $type => $names) {
            foreach ($names as $name) {
                DB::table('skills')->updateOrInsert(['name' => $name], ['type' => $type, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
