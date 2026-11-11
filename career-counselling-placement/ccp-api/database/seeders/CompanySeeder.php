<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;

class CompanySeeder extends DemoSeeder
{
    public function run(): void
    {
        $this->ensureDemoEnvironment();
        $co = Company::firstOrCreate(['name' => 'Demo Cedar Software'], ['verified' => true, 'industry' => 'Software', 'description' => 'Fictional local demonstration company.']);
        Company::firstOrCreate(['name' => 'Demo Maple Systems'], ['verified' => false]);
        User::where('email', 'recruiter@demo.test')->update(['company_id' => $co->id]);
    }
}
