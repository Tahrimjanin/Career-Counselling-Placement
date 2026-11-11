<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_active_administrator_can_be_created_without_demo_credentials(): void
    {
        $this->artisan('ccp:bootstrap-admin')
            ->expectsQuestion('Admin name', 'Initial Admin')
            ->expectsQuestion('Admin email', 'initial-admin@example.com')
            ->expectsQuestion('Password', 'SecurePassword123')
            ->expectsQuestion('Confirm password', 'SecurePassword123')
            ->expectsOutputToContain('Initial administrator created.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'Initial Admin',
            'email' => 'initial-admin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);
    }

    public function test_bootstrap_command_cannot_create_another_active_administrator(): void
    {
        User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->artisan('ccp:bootstrap-admin')
            ->expectsOutputToContain('An active administrator already exists.')
            ->assertExitCode(1);

        $this->assertSame(1, User::where('role', 'admin')->where('status', 'active')->count());
    }

    public function test_migration_soft_deletes_demo_accounts_and_revokes_their_tokens(): void
    {
        $demo = User::factory()->create(['email' => 'student@demo.test']);
        $demo->createToken('demo-session');

        $migration = require database_path('migrations/2026_11_06_000001_soft_delete_demo_accounts.php');
        $migration->up();

        $this->assertNotNull(User::withTrashed()->findOrFail($demo->id)->deleted_at);
        $this->assertSame(0, $demo->tokens()->count());
        $this->postJson('/api/login', [
            'email' => $demo->email,
            'password' => 'password',
            'role' => 'student',
        ])->assertUnprocessable();
    }
}
