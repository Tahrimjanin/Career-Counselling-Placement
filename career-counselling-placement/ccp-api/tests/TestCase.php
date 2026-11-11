<?php

namespace Tests;

use Database\Seeders\AdvisorSeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DemoWorkflowSeeder;
use Database\Seeders\OpportunitySeeder;
use Database\Seeders\RoleUserSeeder;
use Database\Seeders\SkillSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        return parent::actingAs($user, $guard ?? 'sanctum');
    }

    protected function seedDemoFixtures(): void
    {
        $this->seed([
            RoleUserSeeder::class,
            SkillSeeder::class,
            AdvisorSeeder::class,
            CompanySeeder::class,
            OpportunitySeeder::class,
            DemoWorkflowSeeder::class,
        ]);
    }
}
