<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ProfileCompletion;
use App\Services\Readiness;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    }

    private function student(array $attrs = []): User
    {
        $u = User::factory()->create($attrs + ['role' => 'student']);
        StudentProfile::create(['user_id' => $u->id]);

        return $u;
    }

    public function test_student_registration_does_not_require_email(): void
    {
        Notification::fake();
        $r = $this->withHeader('Origin', 'http://localhost:5173')->postJson('/api/register', ['name' => 'Test Student', 'email' => 'NEW@KYAU.EDU.BD', 'password' => 'Password1234', 'password_confirmation' => 'Password1234', 'department' => 'CSE', 'role' => 'student']);
        $r->assertCreated()->assertJsonPath('data.role', 'student')->assertJsonPath('data.email_verified', false)->assertJsonPath('data.email_verification_required', false)->assertJsonStructure(['token']);
        $u = User::where('email', 'new@kyau.edu.bd')->firstOrFail();
        Notification::assertNothingSent();
        $this->assertSame('pending', $u->profile->verification_status);
    }

    public function test_personal_email_allowed_but_password_confirmation_required(): void
    {
        $this->postJson('/api/register', ['name' => 'A', 'email' => 'a@example.com', 'department' => 'CSE', 'password' => 'Password1234', 'password_confirmation' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    public function test_role_mismatch_never_authenticates(): void
    {
        $u = $this->student(['password' => 'Password1234']);
        $this->withHeader('Origin', 'http://localhost:5173')->postJson('/api/login', ['email' => $u->email, 'password' => 'Password1234', 'role' => 'admin'])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertGuest('web');
    }

    public function test_login_throttles_email_and_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'throttle@demo.test', 'password' => 'wrong', 'role' => 'student'])->assertUnprocessable();
        }
        $this->postJson('/api/login', ['email' => 'throttle@demo.test', 'password' => 'wrong', 'role' => 'student'])->assertStatus(429);
    }

    public function test_token_login_logout_and_resources(): void
    {
        $u = $this->student(['password' => 'Password1234']);
        $response = $this->postJson('/api/login', ['email' => $u->email, 'password' => 'Password1234', 'role' => 'student'])->assertOk()->assertJsonMissingPath('data.password')->assertJsonStructure(['token']);
        $this->assertSame([], $response->headers->getCookies());
        $this->withToken($response->json('token'))->getJson('/api/me')->assertOk();
        $this->postJson('/api/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        app('auth')->forgetGuards();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_suspended_user_cannot_sign_in(): void
    {
        $u = $this->student(['status' => 'suspended', 'password' => 'Password1234']);
        $this->postJson('/api/login', ['email' => $u->email, 'password' => 'Password1234', 'role' => 'student'])->assertForbidden();
    }

    public function test_unverified_users_are_gated_and_can_resend(): void
    {
        config(['ccp.require_email_verification' => true]);
        Notification::fake();
        $u = $this->student(['email_verified_at' => null]);
        $this->actingAs($u, 'sanctum');
        $this->getJson('/api/v1/profile')->assertForbidden();
        $this->postJson('/api/email/verification-notification')->assertOk();
        Notification::assertSentTo($u, VerifyEmail::class);
    }

    public function test_signed_verification_and_cross_user_protection(): void
    {
        config(['ccp.require_email_verification' => true]);
        $u = $this->student(['email_verified_at' => null]);
        $other = $this->student(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $u->id, 'hash' => sha1($u->email)]);
        $this->actingAs($other, 'sanctum')->getJson($url)->assertForbidden();
        $this->actingAs($u, 'sanctum')->getJson($url.'&tampered=1')->assertForbidden();
        $this->getJson($url)->assertOk();
        $this->assertTrue($u->fresh()->hasVerifiedEmail());
    }

    public function test_expired_verification_link_is_rejected(): void
    {
        config(['ccp.require_email_verification' => true]);
        $u = $this->student(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $u->id, 'hash' => sha1($u->email)]);
        $this->actingAs($u, 'sanctum')->getJson($url)->assertForbidden();
    }

    public function test_password_reset_uses_single_use_token(): void
    {
        Notification::fake();
        $u = $this->student();
        $this->postJson('/api/forgot-password', ['email' => $u->email])->assertOk();
        Notification::assertSentTo($u, ResetPassword::class);
        $token = Password::createToken($u);
        $body = ['email' => $u->email, 'token' => $token, 'password' => 'Changed12345', 'password_confirmation' => 'Changed12345'];
        $this->postJson('/api/reset-password', $body)->assertOk();
        $this->assertTrue(Hash::check('Changed12345', $u->fresh()->password));
        $this->postJson('/api/reset-password', $body)->assertUnprocessable();
    }

    public function test_forced_password_change_requires_current_password(): void
    {
        $u = $this->student(['must_change_password' => true, 'password' => 'Password1234']);
        $this->actingAs($u, 'sanctum')->getJson('/api/v1/profile')->assertForbidden();
        $this->withHeader('Origin', 'http://localhost:5173')->postJson('/api/change-password', ['current_password' => 'wrong', 'password' => 'Changed12345', 'password_confirmation' => 'Changed12345'])->assertUnprocessable();
        $this->postJson('/api/change-password', ['current_password' => 'Password1234', 'password' => 'Changed12345', 'password_confirmation' => 'Changed12345'])->assertOk()->assertJsonPath('data.must_change_password', false);
    }

    public function test_students_cannot_create_privileged_users(): void
    {
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/users', ['role' => 'admin'])->assertForbidden();
    }

    public function test_admin_created_account_requires_password_change(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::create(['name' => 'Recruiter Company']);
        foreach (['student', 'advisor', 'admin', 'recruiter'] as $role) {
            $payload = ['name' => 'New '.ucfirst($role), 'email' => 'new-'.$role.'@example.com', 'role' => $role, 'password' => 'Temporary1234'];
            if ($role === 'recruiter') {
                $payload['company_id'] = $company->id;
            }
            $response = $this->actingAs($admin, 'sanctum')->postJson('/api/users', $payload)
                ->assertCreated()
                ->assertJsonPath('data.role', $role)
                ->assertJsonPath('data.must_change_password', true)
                ->assertJsonMissingPath('data.password');
            $id = $response->json('data.id');
            if ($role === 'student') {
                $this->assertDatabaseHas('student_profiles', ['user_id' => $id]);
            } elseif ($role === 'advisor') {
                $this->assertDatabaseHas('advisor_profiles', ['user_id' => $id]);
            }
            $this->assertDatabaseHas('audit_logs', [
                'actor_id' => $admin->id,
                'action' => 'user.created',
                'entity_id' => $id,
            ]);
        }
    }

    public function test_partial_profile_save_resume_and_ownership(): void
    {
        $u = $this->student();
        $other = $this->student(['name' => 'Untouched']);
        $this->actingAs($u, 'sanctum');
        $this->patchJson('/api/v1/profile/steps/2', ['department' => 'CSE', 'batch' => '17', 'user_id' => $other->id, 'verification_status' => 'verified', 'advance' => true])->assertOk()->assertJsonPath('data.profile.wizard_step', 3)->assertJsonPath('data.profile.verification_status', 'pending');
        $this->assertNull($other->profile->department);
        $this->getJson('/api/v1/profile')->assertJsonPath('data.profile.department', 'CSE');
    }

    public function test_profile_input_validation_and_server_owned_fields(): void
    {
        $u = $this->student();
        $this->actingAs($u, 'sanctum')->patchJson('/api/v1/profile/steps/2', ['cgpa' => 4.1, 'ssc_gpa' => 6])->assertUnprocessable()->assertJsonValidationErrors(['cgpa', 'ssc_gpa']);
        $this->patchJson('/api/v1/profile/steps/1', ['name' => 'Updated', 'role' => 'admin', 'email' => 'changed@demo.test'])->assertOk();
        $this->assertSame('student', $u->fresh()->role->value);
        $this->assertNotSame('changed@demo.test', $u->fresh()->email);
    }

    public function test_duplicate_student_number_is_rejected(): void
    {
        $a = $this->student();
        $a->profile->update(['student_no' => 'UNIQUE']);
        $b = $this->student();
        $this->actingAs($b, 'sanctum')->patchJson('/api/v1/profile/steps/2', ['student_no' => 'UNIQUE'])->assertUnprocessable();
    }

    public function test_skill_replacement_is_scoped_to_category_and_user(): void
    {
        $u = $this->student();
        $this->actingAs($u, 'sanctum');
        $this->patchJson('/api/v1/profile/steps/3', ['skills' => ['Python', 'SQL']])->assertOk();
        $this->patchJson('/api/v1/profile/steps/4', ['skills' => ['Teamwork']])->assertOk();
        $this->patchJson('/api/v1/profile/steps/3', ['skills' => ['React']])->assertOk()->assertJsonPath('data.technical_skills', ['React'])->assertJsonPath('data.soft_skills', ['Teamwork']);
        $this->patchJson('/api/v1/profile/steps/4', ['skills' => ['React']])->assertUnprocessable();
        $this->assertDatabaseHas('student_skill', ['user_id' => $u->id, 'skill_id' => DB::table('skills')->where('name', 'Teamwork')->value('id')]);
    }

    public function test_experience_not_applicable_clears_entries_and_counts_complete(): void
    {
        $u = $this->student();
        $this->actingAs($u, 'sanctum');
        $this->patchJson('/api/v1/profile/steps/5', ['has_experience' => true, 'entries' => [['title' => 'Developer', 'org' => 'Demo']]])->assertOk();
        $this->patchJson('/api/v1/profile/steps/5', ['has_experience' => false])->assertOk()->assertJsonPath('data.experiences', []);
        $this->assertNotContains('experience', app(ProfileCompletion::class)->calculate($u->fresh()->load('profile'))['missing']);
    }

    public function test_profile_completeness_does_not_imply_readiness(): void
    {
        $this->seedDemoFixtures();
        $u = User::where('email', 'stage1@demo.test')->firstOrFail();
        $this->assertSame(100, app(ProfileCompletion::class)->calculate($u->load('profile'))['percent']);
        DB::table('cv_reviews')->delete();
        DB::table('plan_milestones')->delete();
        DB::table('cvs')->where('user_id', $u->id)->update(['status' => 'draft']);
        $this->assertSame('Not started', app(Readiness::class)->calculate($u)['state']);
    }

    public function test_readiness_requires_all_three_components(): void
    {
        $this->seedDemoFixtures();
        $u = User::where('email', 'stage1@demo.test')->firstOrFail();
        $service = app(Readiness::class);
        $this->assertSame('Placement-ready', $service->calculate($u)['state']);
        DB::table('plan_milestones')->where('plan_id', DB::table('career_plans')->where('student_id', $u->id)->value('id'))->update(['status' => 'in_progress']);
        $this->assertSame('In progress', $service->calculate($u)['state']);
    }

    public function test_student_cannot_verify_self_or_read_other_student_verification(): void
    {
        $u = $this->student();
        $other = $this->student();
        $this->actingAs($u, 'sanctum');
        $this->getJson('/api/v1/student-verifications')->assertForbidden();
        $this->getJson('/api/v1/student-verifications/'.$other->id)->assertForbidden();
        $this->patchJson('/api/v1/student-verifications/'.$u->id, ['status' => 'verified'])->assertForbidden();
    }

    public function test_advisor_and_recruiter_cannot_verify_students(): void
    {
        $u = $this->student();
        foreach (['advisor', 'recruiter'] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor, 'sanctum')->patchJson('/api/v1/student-verifications/'.$u->id, ['status' => 'verified'])->assertForbidden();
        }
    }

    public function test_admin_verification_requires_email_and_rejection_reason(): void
    {
        config(['ccp.require_email_verification' => true]);
        $u = $this->student(['email_verified_at' => null]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum');
        $this->patchJson('/api/v1/student-verifications/'.$u->id, ['status' => 'verified'])->assertUnprocessable();
        $u->markEmailAsVerified();
        $this->patchJson('/api/v1/student-verifications/'.$u->id, ['status' => 'rejected'])->assertUnprocessable();
        $this->patchJson('/api/v1/student-verifications/'.$u->id, ['status' => 'verified'])->assertOk()->assertJsonPath('data.profile.verification_status', 'verified');
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'student.verification']);
    }

    public function test_pending_students_cannot_apply_but_can_browse(): void
    {
        $this->actingAs($this->student(), 'sanctum')->postJson('/api/v1/applications', [])->assertForbidden();
        $this->getJson('/api/opportunities')->assertOk();
        $this->getJson('/api/v1/profile')->assertOk();
    }

    public function test_academic_edits_reset_verification(): void
    {
        $u = $this->student();
        $u->profile->update(['verification_status' => 'verified']);
        $this->actingAs($u, 'sanctum')->patchJson('/api/v1/profile/steps/2', ['batch' => 'Changed'])->assertOk()->assertJsonPath('data.profile.verification_status', 'pending');
    }

    public function test_non_student_can_edit_contact_but_not_student_fields(): void
    {
        $u = User::factory()->create(['role' => 'advisor']);
        $this->actingAs($u, 'sanctum')->patchJson('/api/v1/profile/steps/1', ['name' => 'Advisor Name'])->assertOk();
        $this->patchJson('/api/v1/profile/steps/2', ['cgpa' => 3])->assertForbidden();
    }

    public function test_generated_column_enforces_one_active_advisor(): void
    {
        $u = $this->student();
        $a = User::factory()->create(['role' => 'advisor']);
        $row = ['student_id' => $u->id, 'advisor_id' => $a->id, 'status' => 'active', 'started_at' => now()];
        DB::table('advisor_assignments')->insert($row);
        DB::table('advisor_assignments')->insert(array_merge($row, ['status' => 'ended']));
        $this->expectException(QueryException::class);
        DB::table('advisor_assignments')->insert($row);
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seedDemoFixtures();
        $count = User::count();
        $this->seedDemoFixtures();
        $this->assertSame($count, User::count());
        $this->assertSame(11, DB::table('applications')->count());
    }

    public function test_no_unauthenticated_profile_access(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
    }

    public function test_saving_unchanged_academics_does_not_revoke_verification(): void
    {
        $u = $this->student();
        $u->profile->update(['department' => 'CSE', 'verification_status' => 'verified']);
        $this->actingAs($u, 'sanctum')->patchJson('/api/v1/profile/steps/2', ['department' => 'CSE'])->assertOk()->assertJsonPath('data.profile.verification_status', 'verified');
    }

    public function test_registration_without_cookie_or_origin_succeeds(): void
    {
        $this->postJson('/api/register', ['name' => 'No Session', 'email' => 'nosession@kyau.edu.bd', 'department' => 'CSE', 'password' => 'Password1234', 'password_confirmation' => 'Password1234'])->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'nosession@kyau.edu.bd']);
    }

    public function test_recruiter_company_profile_cannot_target_other_company(): void
    {
        $a = Company::create(['name' => 'Owned']);
        $b = Company::create(['name' => 'Other']);
        $u = User::factory()->create(['role' => 'recruiter', 'company_id' => $a->id]);
        $this->actingAs($u, 'sanctum')->patchJson('/api/v1/profile/steps/1', ['company' => ['name' => 'Changed'], 'company_id' => $b->id])->assertOk()->assertJsonPath('data.company.name', 'Changed');
        $this->assertSame('Other', $b->fresh()->name);
        $this->patchJson('/api/v1/profile/steps/1', ['company' => ['website' => 'javascript:alert(1)']])->assertUnprocessable();
    }

    public function test_nested_profile_entries_cannot_spoof_ownership(): void
    {
        $u = $this->student();
        $other = $this->student();
        $this->actingAs($u, 'sanctum')->patchJson('/api/v1/profile/steps/5', ['has_experience' => true, 'entries' => [['title' => 'Role', 'org' => 'Demo', 'user_id' => $other->id]]])->assertUnprocessable();
        $this->assertDatabaseCount('profile_experiences', 0);
    }
}
