<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Cv;
use App\Models\Opportunity;
use App\Models\User;
use App\Notifications\PlatformMail;
use App\Services\PlatformSettings;
use App\Services\ReminderScheduler;
use App\Services\WorkflowActivity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Queue::fake();
    }

    private function user($role = 'student')
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function application($status = 'submitted')
    {
        $u = $this->user();
        $c = Company::create(['name' => 'Demo Company', 'verified' => true]);
        $o = Opportunity::create(['company_id' => $c->id, 'title' => 'Engineer', 'type' => 'Job', 'location' => 'Dhaka', 'description' => 'Role', 'deadline' => now('Asia/Dhaka')->addDays(3), 'status' => 'open']);
        $cv = Cv::create(['user_id' => $u->id, 'version' => 1, 'status' => 'approved', 'path' => '']);

        return Application::create(['user_id' => $u->id, 'student_id' => $u->id, 'opportunity_id' => $o->id, 'cv_id' => $cv->id, 'workflow_status' => $status, 'status' => $status]);
    }

    public function test_all_four_roles_register_with_safe_status_and_required_profiles()
    {
        foreach (['student', 'advisor', 'recruiter', 'admin'] as $role) {
            Cache::flush();
            $r = $this->postJson('/api/register', ['role' => $role, 'name' => 'বাংলা Name', 'email' => $role.'@example.com', 'department' => 'CSE', 'company_name' => 'New Company', 'password' => 'Password1234', 'password_confirmation' => 'Password1234', 'status' => 'active']);
            $r->assertCreated()->assertJsonPath('data.role', $role)->assertJsonPath('data.status', $role === 'student' ? 'active' : 'pending');
            $id = $r->json('data.id');
            if ($role === 'student') {
                $this->assertDatabaseHas('student_profiles', ['user_id' => $id]);
            }if ($role === 'advisor') {
                $this->assertDatabaseHas('advisor_profiles', ['user_id' => $id, 'capacity' => 20]);
            }if ($role === 'recruiter') {
                $this->assertFalse((bool) Company::find(User::find($id)->company_id)->verified);
            }
        }
    }

    public function test_pending_admin_can_login_but_cannot_use_privileged_endpoints_until_approved()
    {
        $u = $this->user('admin');
        $u->update(['status' => 'pending', 'password' => 'Password1234']);
        $r = $this->postJson('/api/login', ['role' => 'admin', 'email' => $u->email, 'password' => 'Password1234'])->assertOk();
        $this->withToken($r->json('token'))->getJson('/api/me')->assertOk();
        foreach (['/api/v1/admin/users', '/api/v1/admin/settings', '/api/v1/admin/reports', '/api/users'] as $url) {
            $this->getJson($url)->assertForbidden();
        }$admin = $this->user('admin');
        $this->actingAs($admin)->patchJson('/api/v1/admin/users/'.$u->id, ['status' => 'active'])->assertOk();
        $this->actingAs($u->fresh())->getJson('/api/v1/admin/reports')->assertOk();
    }

    public function test_registration_rejects_invalid_roles_missing_company_and_duplicate_email()
    {
        $data = ['name' => 'A', 'email' => 'a@example.com', 'password' => 'Password1234', 'password_confirmation' => 'Password1234'];
        $this->postJson('/api/register', $data + ['role' => 'owner'])->assertUnprocessable();
        $this->postJson('/api/register', $data + ['role' => 'recruiter'])->assertUnprocessable()->assertJsonValidationErrors('company_name');
        $this->postJson('/api/register', $data)->assertUnprocessable()->assertJsonValidationErrors('department');
        Cache::flush();
        $this->postJson('/api/register', $data + ['role' => 'advisor'])->assertCreated();
        $this->postJson('/api/register', $data + ['role' => 'advisor'])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_admin_user_management_revokes_tokens_and_protects_self()
    {
        $a = $this->user('admin');
        $u = $this->user();
        $u->createToken('test');
        $this->actingAs($u)->patchJson('/api/v1/admin/users/'.$a->id, ['status' => 'suspended'])->assertForbidden();
        $this->actingAs($a)->patchJson('/api/v1/admin/users/'.$a->id, ['status' => 'suspended'])->assertUnprocessable();
        $this->patchJson('/api/v1/admin/users/'.$u->id, ['password' => 'Temporary1234'])->assertOk();
        $this->assertTrue($u->fresh()->must_change_password);
        $this->assertSame(0, $u->tokens()->count());
        $this->assertStringNotContainsString('Temporary1234', DB::table('audit_logs')->value('new_values'));
        $this->patchJson('/api/v1/admin/users/'.$u->id, ['status' => 'suspended'])->assertOk();
        $this->getJson('/api/v1/admin/users?status=suspended')->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_admin_can_promote_an_existing_account_without_changing_its_work_data()
    {
        $admin = $this->user('admin');
        $advisor = $this->user('advisor');
        $advisor->update(['password' => 'Password1234']);
        DB::table('advisor_profiles')->insert([
            'user_id' => $advisor->id,
            'title' => 'Career Advisor',
            'bio' => 'Existing advisor profile.',
            'capacity' => 20,
            'is_accepting' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('advisor_expertise')->insert([
            'advisor_id' => $advisor->id,
            'tag' => 'Career planning',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $company = Company::create(['name' => 'Preserved Company', 'verified' => true]);
        $opportunity = Opportunity::create([
            'company_id' => $company->id,
            'title' => 'Preserved role',
            'type' => 'Job',
            'location' => 'Dhaka',
            'description' => 'Existing application data.',
            'status' => 'open',
        ]);
        $cv = Cv::create(['user_id' => $advisor->id, 'version' => 1, 'status' => 'approved', 'path' => '']);
        $application = Application::create([
            'user_id' => $advisor->id,
            'student_id' => $advisor->id,
            'opportunity_id' => $opportunity->id,
            'cv_id' => $cv->id,
            'workflow_status' => 'submitted',
            'status' => 'Applied',
        ]);
        $advisor->createToken('before-promotion');
        $advisorId = $advisor->id;

        $student = $this->user('student');
        $this->actingAs($admin)->patchJson('/api/v1/admin/users/'.$student->id, ['role' => 'admin'])->assertUnprocessable();
        $this->patchJson('/api/v1/admin/users/'.$advisorId, ['role' => 'admin'])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $advisorId, 'role' => 'admin', 'status' => 'active']);
        $this->assertDatabaseHas('advisor_profiles', ['user_id' => $advisorId, 'bio' => 'Existing advisor profile.']);
        $this->assertDatabaseHas('advisor_expertise', ['advisor_id' => $advisorId, 'tag' => 'Career planning']);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'user_id' => $advisorId]);
        $this->assertDatabaseHas('cvs', ['id' => $cv->id, 'user_id' => $advisorId]);
        $this->assertSame(0, $advisor->tokens()->count());

        $login = $this->postJson('/api/login', [
            'email' => $advisor->email,
            'password' => 'Password1234',
            'role' => 'admin',
        ])->assertOk()->assertJsonPath('data.id', $advisorId)->assertJsonPath('data.role', 'admin');
        app('auth')->forgetGuards();
        $this->withToken($login->json('token'))->getJson('/api/v1/admin/users')->assertOk();
    }

    public function test_admin_can_remove_accounts_without_erasing_associated_data()
    {
        $admin = $this->user('admin');
        $application = $this->application();
        $student = $application->user;
        $student->createToken('before-removal');
        $studentId = $student->id;

        $this->actingAs($admin)->deleteJson('/api/v1/admin/users/'.$studentId)
            ->assertOk()->assertJsonPath('message', 'Account removed.');

        $this->assertDatabaseHas('users', ['id' => $studentId]);
        $this->assertNotNull(User::withTrashed()->findOrFail($studentId)->deleted_at);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'user_id' => $studentId]);
        $this->assertDatabaseHas('cvs', ['id' => $application->cv_id, 'user_id' => $studentId]);
        $this->assertSame(0, $student->tokens()->count());
        $this->deleteJson('/api/v1/admin/users/'.$admin->id)->assertUnprocessable();
    }

    public function test_default_database_seed_does_not_create_demo_logins()
    {
        $this->seed();

        $this->assertSame(0, User::count());
        $this->assertGreaterThan(0, DB::table('skills')->count());
    }

    public function test_avatar_upload_private_fetch_remove_and_spoof_rejection()
    {
        Storage::fake('local');
        $u = $this->user();
        $this->actingAs($u)->postJson('/api/v1/avatar', ['image' => UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j2ioAAAAASUVORK5CYII='))])->assertOk();
        $path = $u->fresh()->avatar_path;
        Storage::disk('local')->assertExists($path);
        $this->get('/api/v1/avatar')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($this->user())->getJson('/api/v1/avatar')->assertNotFound();
        $this->actingAs($u)->postJson('/api/v1/avatar', ['image' => UploadedFile::fake()->createWithContent('bad.png', '<script>alert(1)</script>')])->assertUnprocessable();
        $this->deleteJson('/api/v1/avatar')->assertOk();
        Storage::disk('local')->assertMissing($path);
        $this->getJson('/api/me')->assertJsonPath('data.has_avatar', false);
    }

    public function test_notifications_are_scoped_filtered_readable_and_deduplicated()
    {
        $u = $this->user();
        $other = $this->user();
        WorkflowActivity::notify([$u->id, $other->id], 'Deadline', 'Body', 'deadlines', '/opportunities', 'unique');
        WorkflowActivity::notify([$u->id], 'Deadline', 'Body', 'deadlines', '/opportunities', 'unique');
        WorkflowActivity::notify([$u->id], 'Interview', null, 'interviews', '/interviews');
        $this->assertSame(2, $u->notifications()->count());
        $this->actingAs($u)->getJson('/api/v1/notifications?filter=deadlines')->assertOk()->assertJsonPath('unread', 2)->assertJsonCount(1, 'data.data');
        $this->postJson('/api/v1/notifications/'.$other->notifications()->first()->id.'/read')->assertNotFound();
        $this->postJson('/api/v1/notifications/'.$u->notifications()->first()->id.'/read')->assertOk();
        $this->getJson('/api/v1/notifications?filter=unread')->assertJsonCount(1, 'data.data');
        $this->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->assertSame(0, $u->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
        Queue::assertPushed(SendQueuedNotifications::class);
    }

    public function test_deadline_scheduler_runs_once_per_recipient_and_threshold()
    {
        $a = $this->application();
        $u = User::find($a->user_id);
        app(ReminderScheduler::class)->run();
        app(ReminderScheduler::class)->run();
        $this->assertSame(1, $u->notifications()->count());
        $this->travel(2)->days();
        app(ReminderScheduler::class)->run();
        $this->assertSame(2, $u->notifications()->count());
        $this->travelBack();
    }

    public function test_followups_enforce_ownership_repeat_and_stop_when_finished()
    {
        $a = $this->application();
        $u = User::find($a->user_id);
        $this->actingAs($this->user())->postJson('/api/v1/reminders', ['application_id' => $a->id, 'interval_days' => 7])->assertForbidden();
        $this->actingAs($u)->postJson('/api/v1/reminders', ['application_id' => $a->id, 'interval_days' => 7])->assertOk();
        $id = DB::table('reminders')->value('id');
        DB::table('reminders')->where('id', $id)->update(['remind_at' => now()->subMinute()]);
        app(ReminderScheduler::class)->run();
        $this->assertTrue(Carbon::parse(DB::table('reminders')->value('remind_at'))->isFuture());
        $a->update(['workflow_status' => 'withdrawn']);
        DB::table('reminders')->where('id', $id)->update(['remind_at' => now()->subMinute()]);
        app(ReminderScheduler::class)->run();
        $this->assertNotNull(DB::table('reminders')->value('sent_at'));
        $this->actingAs($this->user())->deleteJson('/api/v1/reminders/'.$id)->assertNotFound();
        $this->actingAs($u)->deleteJson('/api/v1/reminders/'.$id)->assertNoContent();
    }

    public function test_settings_public_content_and_reports_are_permission_scoped()
    {
        $u = $this->user();
        $this->actingAs($u)->getJson('/api/v1/admin/settings')->assertForbidden();
        $a = $this->user('admin');
        $this->actingAs($a)->putJson('/api/v1/admin/settings', array_replace(PlatformSettings::DEFAULTS, ['default_capacity' => 12, 'contact_email' => 'office@example.com']))->assertOk();
        $this->getJson('/api/public/overview')->assertOk()->assertJsonPath('data.settings.contact_email', 'office@example.com')->assertJsonMissingPath('data.settings.default_capacity');
        $this->getJson('/api/v1/admin/audit')->assertOk()->assertJsonPath('data.data.0.action', 'settings.updated');
        $this->getJson('/api/v1/admin/reports?from=2026-11-01&to=2026-10-01')->assertUnprocessable();
        $this->getJson('/api/v1/admin/reports?to=2026-10-01')->assertOk();
    }

    public function test_admin_can_customize_public_site_and_invalid_section_order_is_rejected()
    {
        $admin = $this->user('admin');
        $this->actingAs($this->user())->putJson('/api/v1/admin/settings', [
            'site_name' => 'KYAU Careers',
        ])->assertForbidden();

        $this->actingAs($admin)->putJson('/api/v1/admin/settings', [
            'site_name' => 'KYAU Careers',
            'theme_primary_color' => '#123456',
            'hero_title_first' => 'Plan your future.',
            'journey_enabled' => '1',
            'journey_home_enabled' => '0',
            'faq_1_question' => 'How do I start?',
            'faq_1_answer' => 'Create an account.',
            'section_order' => 'advisors,journey,opportunities,faq,contact',
        ])->assertOk()->assertJsonPath('data.site_name', 'KYAU Careers');

        $this->getJson('/api/public/overview')->assertOk()
            ->assertJsonPath('data.settings.site_name', 'KYAU Careers')
            ->assertJsonPath('data.settings.theme_primary_color', '#123456')
            ->assertJsonPath('data.settings.hero_title_first', 'Plan your future.')
            ->assertJsonPath('data.settings.journey_home_enabled', false)
            ->assertJsonPath('data.settings.section_order.0', 'advisors')
            ->assertJsonPath('data.settings.faqs.0.question', 'How do I start?')
            ->assertJsonMissingPath('data.settings.default_capacity')
            ->assertJsonMissingPath('data.settings.brand_logo_path');
        $this->getJson('/api/public/settings')->assertOk()
            ->assertJsonPath('data.settings.site_name', 'KYAU Careers')
            ->assertJsonMissingPath('data.settings.default_capacity')
            ->assertJsonMissingPath('data.settings.brand_logo_path');

        $this->actingAs($admin)->putJson('/api/v1/admin/settings', [
            'section_order' => 'advisors,journey,journey,faq,contact',
        ])->assertUnprocessable();
        $this->actingAs($admin)->putJson('/api/v1/admin/settings', [
            'theme_primary_color' => '#FFFFFF',
        ])->assertUnprocessable()->assertJsonValidationErrors('theme_primary_color');
    }

    public function test_admin_site_logo_upload_validates_image_and_can_be_removed()
    {
        Storage::fake('local');
        $admin = $this->user('admin');
        $validPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j2ioAAAAASUVORK5CYII=');

        $this->actingAs($admin)->postJson('/api/v1/admin/settings/logo', [
            'image' => UploadedFile::fake()->createWithContent('logo.png', $validPng),
        ])->assertOk();
        $path = PlatformSettings::get('brand_logo_path');
        Storage::disk('local')->assertExists($path);
        $this->get('/api/public/site-logo')->assertOk()
            ->assertHeader('Content-Type', 'image/png');
        $logoUrl = $this->getJson('/api/public/overview')->json('data.settings.logo_url');
        $this->assertStringContainsString('/api/public/site-logo', $logoUrl);

        $this->actingAs($admin)->postJson('/api/v1/admin/settings/logo', [
            'image' => UploadedFile::fake()->createWithContent('bad.png', '<script>bad</script>'),
        ])->assertUnprocessable();
        $this->deleteJson('/api/v1/admin/settings/logo')->assertOk();
        Storage::disk('local')->assertMissing($path);
        $this->getJson('/api/public/site-logo')->assertNotFound();
    }

    public function test_report_cohort_counts_placement_once_and_csv_escapes_formula_names()
    {
        $a = $this->application();
        $this->user('advisor')->update(['name' => '=SUM(1,2)']);
        DB::table('placement_records')->insert(['application_id' => $a->id, 'student_id' => $a->user_id, 'company_id' => $a->opportunity->company_id, 'role_title' => 'Engineer', 'job_type' => 'Job', 'start_date' => now()->toDateString(), 'recorded_by' => $a->user_id, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->user('admin'))->getJson('/api/v1/admin/reports')->assertOk()->assertJsonPath('data.applicants', 1)->assertJsonPath('data.placed_students', 1)->assertJsonPath('data.placement_rate', 100);
        $this->getJson('/api/v1/admin/reports?from='.now()->addDay()->toDateString())->assertJsonPath('data.applicants', 0)->assertJsonPath('data.placement_rate', 0);
        $csv = $this->get('/api/v1/admin/reports.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=SUM(1,2)", $csv);
    }

    public function test_session_and_interview_reminders_are_idempotent_and_reschedule_aware(): void
    {
        $a = $this->application('interview');
        $advisor = $this->user('advisor');
        $recruiter = $this->user('recruiter');
        $sid = DB::table('counselling_sessions')->insertGetId(['student_id' => $a->user_id, 'advisor_id' => $advisor->id, 'scheduled_at' => now()->addHours(20), 'ends_at' => now()->addHours(20)->addMinutes(30), 'type' => 'career', 'mode' => 'online', 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);
        $iid = DB::table('interviews')->insertGetId(['application_id' => $a->id, 'scheduled_by' => $recruiter->id, 'scheduled_at' => now()->addHours(20), 'duration_min' => 30, 'mode' => 'online', 'status' => 'scheduled', 'created_at' => now(), 'updated_at' => now()]);
        app(ReminderScheduler::class)->run();
        app(ReminderScheduler::class)->run();
        $this->assertSame(1, $advisor->notifications()->count());
        $this->assertSame(1, $recruiter->notifications()->count());
        $this->travel(19)->hours();
        app(ReminderScheduler::class)->run();
        app(ReminderScheduler::class)->run();
        $this->assertSame(2, $advisor->notifications()->count());
        $this->assertSame(2, $recruiter->notifications()->count());
        DB::table('interviews')->where('id', $iid)->update(['scheduled_at' => now()->addHours(10)]);
        app(ReminderScheduler::class)->run();
        $this->assertSame(3, $recruiter->notifications()->count());
        DB::table('counselling_sessions')->where('id', $sid)->update(['status' => 'cancelled']);
        DB::table('interviews')->where('id', $iid)->update(['status' => 'cancelled']);
        app(ReminderScheduler::class)->run();
        $this->assertSame(2, $advisor->notifications()->count());
        $this->travelBack();
    }

    public function test_suspended_recipients_receive_no_notifications_and_mail_links_are_local_workspace_paths(): void
    {
        $u = $this->user();
        $u->update(['status' => 'suspended']);
        WorkflowActivity::notify([$u->id], 'Hidden');
        $this->assertSame(0, $u->notifications()->count());
        $mail = (new PlatformMail(['title' => 'Review', 'body' => 'Check your CV', 'url' => '/cv']))->toMail($u);
        $this->assertSame('Review', $mail->subject);
        $this->assertSame(rtrim(config('app.frontend_url'), '/').'/cv', $mail->actionUrl);
    }
}
