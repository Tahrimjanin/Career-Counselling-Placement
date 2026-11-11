<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

abstract class DemoSeeder extends Seeder
{
    protected function ensureDemoEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo fixtures can only be seeded in local or testing environments.');
        }
    }
}
