<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class SimpleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function account(array $data = []): User
    {
        return User::factory()->create($data + ['role' => 'student', 'password' => 'Password1234', 'email_verified_at' => null]);
    }

    private function loginToken(User $u): string
    {
        return $this->postJson('/api/login', ['email' => $u->email, 'password' => 'Password1234', 'role' => $u->role->value])->assertOk()->json('token');
    }

    public function test_existing_unverified_account_can_login_without_cookies_or_app_key(): void
    {
        config(['app.key' => null]);
        $u = $this->account();
        $token = $this->loginToken($u);
        $res = $this->withToken($token)->getJson('/api/v1/profile')->assertOk();
        $this->assertSame([], $res->headers->getCookies());
        $this->assertFalse($u->fresh()->hasVerifiedEmail());
    }

    public function test_registration_enters_profile_without_sending_verification_or_granting_approval(): void
    {
        Notification::fake();
        $res = $this->postJson('/api/register', ['name' => 'New Student', 'email' => 'new@example.com', 'password' => 'Password1234', 'password_confirmation' => 'Password1234', 'department' => 'CSE'])->assertCreated()->assertJsonPath('data.email_verification_required', false);
        $this->withToken($res->json('token'))->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.profile.verification_status', 'pending');
        $this->postJson('/api/v1/applications', [])->assertForbidden();
        Notification::assertNothingSent();
    }

    public function test_random_expired_and_revoked_tokens_cannot_access_protected_data(): void
    {
        $this->withToken('invalid')->getJson('/api/me')->assertUnauthorized();
        app('auth')->forgetGuards();
        $u = $this->account();
        $token = $this->loginToken($u);
        PersonalAccessToken::findToken($token)->update(['expires_at' => now()->subMinute()]);
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $token = $u->createToken('test')->plainTextToken;
        PersonalAccessToken::findToken($token)->delete();
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_suspended_account_is_blocked_even_with_existing_valid_token(): void
    {
        $u = $this->account();
        $token = $this->loginToken($u);
        $u->update(['status' => 'suspended']);
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/profile')->assertForbidden();
    }

    public function test_password_change_revokes_other_tokens_and_keeps_current_browser(): void
    {
        $u = $this->account(['must_change_password' => true]);
        $one = $this->loginToken($u);
        $two = $this->loginToken($u);
        $this->withToken($one)->postJson('/api/change-password', ['current_password' => 'Password1234', 'password' => 'Changed12345', 'password_confirmation' => 'Changed12345'])->assertOk();
        $this->assertNotNull(PersonalAccessToken::findToken($one));
        $this->assertNull(PersonalAccessToken::findToken($two));
    }

    public function test_password_reset_revokes_all_existing_tokens(): void
    {
        $u = $this->account();
        $this->loginToken($u);
        $token = Password::createToken($u);
        $this->postJson('/api/reset-password', ['email' => $u->email, 'token' => $token, 'password' => 'Changed12345', 'password_confirmation' => 'Changed12345'])->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_can_approve_unverified_email_when_email_checks_are_deferred(): void
    {
        $s = $this->account();
        StudentProfile::create(['user_id' => $s->id]);
        $admin = $this->account(['role' => 'admin']);
        $token = $this->loginToken($admin);
        $this->withToken($token)->patchJson('/api/v1/student-verifications/'.$s->id, ['status' => 'verified'])->assertOk();
        $this->assertFalse($s->fresh()->hasVerifiedEmail());
    }

    public function test_admin_created_staff_receive_no_verification_email_but_still_change_password(): void
    {
        Notification::fake();
        $admin = $this->account(['role' => 'admin']);
        $token = $this->loginToken($admin);
        $this->withToken($token)->postJson('/api/users', ['name' => 'Advisor', 'email' => 'advisor@example.com', 'role' => 'advisor', 'password' => 'Temporary1234'])->assertCreated()->assertJsonPath('data.email_verification_required', false)->assertJsonPath('data.must_change_password', true);
        Notification::assertNothingSent();
    }
}
