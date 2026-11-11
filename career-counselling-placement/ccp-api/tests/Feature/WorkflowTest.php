<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Cv;
use App\Models\Opportunity;
use App\Models\StudentProfile;
use App\Models\User;
use App\Policies\WorkflowPolicy;
use App\Services\AdvisorAllocation;
use App\Services\ApplicationWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'student'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function advisor(int $capacity = 20): User
    {
        $a = $this->user('advisor');
        DB::table('advisor_profiles')->insert(['user_id' => $a->id, 'capacity' => $capacity, 'is_accepting' => true, 'created_at' => now(), 'updated_at' => now()]);

        return $a;
    }

    private function assign(User $s, User $a): void
    {
        (new AdvisorAllocation)->assign($s->id, $a->id, $this->user('admin')->id);
    }

    private function opportunity(): Opportunity
    {
        $c = Company::create(['name' => 'Fictional Company', 'verified' => true]);

        return Opportunity::create(['company_id' => $c->id, 'title' => 'Engineer', 'type' => 'Job', 'location' => 'Dhaka', 'description' => 'Test vacancy', 'deadline' => now()->addDays(10), 'status' => 'open', 'skills' => ['PHP']]);
    }

    private function application(string $status = 'submitted'): Application
    {
        $s = $this->user();
        $o = $this->opportunity();
        $cv = Cv::create(['user_id' => $s->id, 'version' => 1, 'status' => 'approved', 'path' => '']);

        return Application::create(['user_id' => $s->id, 'student_id' => $s->id, 'opportunity_id' => $o->id, 'cv_id' => $cv->id, 'workflow_status' => $status, 'status' => $status, 'forwarded_at' => in_array($status, ['forwarded', 'shortlisted', 'interview', 'offer']) ? now() : null]);
    }

    public function test_all_roles_login_and_each_wrong_role_is_rejected(): void
    {
        foreach (['student', 'advisor', 'admin', 'recruiter'] as $role) {
            $u = $this->user($role);
            $u->update(['password' => 'Password1234']);
            $login = $this->withHeader('Origin', 'http://localhost:5173')->postJson('/api/login', ['email' => $u->email, 'password' => 'Password1234', 'role' => $role])->assertOk()->assertJsonPath('data.role', $role);
            $this->withToken($login->json('token'))->postJson('/api/logout')->assertNoContent();
            app('auth')->forgetGuards();
            foreach (array_diff(['student', 'advisor', 'admin', 'recruiter'], [$role]) as $wrong) {
                $this->postJson('/api/login', ['email' => $u->email, 'password' => 'Password1234', 'role' => $wrong])->assertUnprocessable()->assertJsonValidationErrors('role');
            }
        }
    }

    public function test_personal_email_registration_is_allowed_and_cannot_escalate_role(): void
    {
        Notification::fake();
        $this->withHeader('Origin', 'http://localhost:5173')->postJson('/api/register', ['name' => 'Fictional Student', 'email' => 'person@example.com', 'password' => 'Password1234', 'password_confirmation' => 'Password1234', 'department' => 'CSE', 'role' => 'admin'])->assertCreated()->assertJsonPath('data.role', 'admin')->assertJsonPath('data.status', 'pending');
    }

    public function test_preferences_do_not_assign_and_capacity_is_rechecked(): void
    {
        $s = $this->user();
        $a = $this->advisor(1);
        $b = $this->advisor();
        $this->actingAs($s)->postJson('/api/v1/advisor-preferences', ['first' => $a->id, 'second' => $b->id])->assertOk();
        $this->assertDatabaseCount('advisor_assignments', 0);
        $admin = $this->user('admin');
        $this->actingAs($admin)->postJson('/api/v1/allocations/'.$s->id, ['advisor_id' => $a->id])->assertOk();
        $this->actingAs($admin)->postJson('/api/v1/allocations/'.$this->user()->id, ['advisor_id' => $a->id])->assertUnprocessable();
        $this->assertDatabaseCount('advisor_assignments', 1);
    }

    public function test_rejecting_preferences_updates_pending_requests_and_removes_them_from_the_queue(): void
    {
        $student = $this->user();
        $first = $this->advisor();
        $second = $this->advisor();
        $this->actingAs($student)->postJson('/api/v1/advisor-preferences', ['first' => $first->id, 'second' => $second->id])->assertOk();

        $admin = $this->user('admin');
        $this->actingAs($admin)->deleteJson('/api/v1/allocations/'.$student->id)->assertOk();
        $this->assertDatabaseHas('advisor_preferences', ['student_id' => $student->id, 'status' => 'rejected']);
        $this->getJson('/api/v1/allocations')->assertOk()->assertJsonCount(0, 'data');
        $this->deleteJson('/api/v1/allocations/'.$student->id)->assertUnprocessable();
    }

    public function test_non_admin_cannot_allocate_and_preferences_must_differ(): void
    {
        $s = $this->user();
        $a = $this->advisor();
        $this->actingAs($s)->postJson('/api/v1/allocations/'.$s->id, ['advisor_id' => $a->id])->assertForbidden();
        $this->postJson('/api/v1/advisor-preferences', ['first' => $a->id, 'second' => $a->id])->assertUnprocessable();
    }

    public function test_student_advisor_list_only_shows_allocated_advisor_after_assignment(): void
    {
        $student = $this->user();
        $allocatedAdvisor = $this->advisor();
        $otherAdvisor = $this->advisor();

        $this->actingAs($student)->getJson('/api/v1/advisors')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('assignment', null);

        $this->assign($student, $allocatedAdvisor);

        $this->getJson('/api/v1/advisors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $allocatedAdvisor->id)
            ->assertJsonPath('assignment.advisor_id', $allocatedAdvisor->id);
        $this->assertNotEquals($allocatedAdvisor->id, $otherAdvisor->id);
    }

    public function test_sessions_block_past_overlap_and_cross_access(): void
    {
        $s = $this->user();
        $a = $this->advisor();
        $this->assign($s, $a);
        $data = ['type' => 'CV review', 'scheduled_at' => now()->addDays(2)->toISOString(), 'duration' => 60, 'mode' => 'Online'];
        $this->actingAs($s)->postJson('/api/v1/sessions', $data)->assertOk();
        $this->postJson('/api/v1/sessions', $data)->assertUnprocessable();
        $this->postJson('/api/v1/sessions', array_replace($data, ['scheduled_at' => now()->subDay()->toISOString()]))->assertUnprocessable();
        $id = DB::table('counselling_sessions')->value('id');
        $this->actingAs($this->user())->patchJson('/api/v1/sessions/'.$id, ['action' => 'cancel', 'cancel_reason' => 'No longer available'])->assertForbidden();
        $this->actingAs($a)->patchJson('/api/v1/sessions/'.$id, ['action' => 'confirm'])->assertOk();
        $this->patchJson('/api/v1/sessions/'.$id, ['action' => 'complete', 'body' => 'Good', 'next_step' => 'Practice'])->assertUnprocessable();
        $this->actingAs($s)->patchJson('/api/v1/sessions/'.$id, ['action' => 'cancel', 'cancel_reason' => 'Conflict'])->assertOk();
    }

    public function test_plan_only_assigned_advisor_can_edit_and_student_cannot_confirm_completion(): void
    {
        $s = $this->user();
        $a = $this->advisor();
        $this->assign($s, $a);
        $data = ['target_role' => 'Developer', 'milestones' => [['title' => 'Practice PHP']]];
        $this->actingAs($this->advisor())->putJson('/api/v1/plans/'.$s->id, $data)->assertForbidden();
        $this->actingAs($a)->putJson('/api/v1/plans/'.$s->id, $data)->assertOk();
        $id = DB::table('plan_milestones')->value('id');
        $this->actingAs($s)->patchJson('/api/v1/milestones/'.$id, ['status' => 'completed'])->assertForbidden();
        $this->patchJson('/api/v1/milestones/'.$id, ['status' => 'in_progress'])->assertOk();
        $this->actingAs($a)->patchJson('/api/v1/milestones/'.$id, ['status' => 'completed'])->assertOk();
        $this->putJson('/api/v1/plans/'.$s->id, $data)->assertOk();
        $this->assertDatabaseHas('plan_milestones', ['id' => $id, 'status' => 'completed']);
    }

    public function test_cv_versions_review_and_private_access(): void
    {
        $s = $this->user();
        $a = $this->advisor();
        $this->assign($s, $a);
        $data = ['title' => 'CV', 'source' => 'builder', 'content' => ['name' => 'Demo', 'email' => 'demo@example.com', 'summary' => 'Summary', 'education' => 'BSc', 'skills' => 'PHP']];
        $id = $this->actingAs($s)->postJson('/api/v1/cvs', $data)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/cvs/'.$id.'/submit')->assertOk();
        $review = ['decision' => 'approved', 'strengths' => 'Good', 'improvements' => 'Keep learning', 'placement_recommendation' => 'ready'];
        $this->actingAs($this->advisor())->postJson('/api/v1/cvs/'.$id.'/review', $review)->assertForbidden();
        $this->actingAs($a)->postJson('/api/v1/cvs/'.$id.'/review', $review)->assertOk();
        $this->postJson('/api/v1/cvs/'.$id.'/review', $review)->assertUnprocessable();
        $this->actingAs($s)->postJson('/api/v1/cvs', $data)->assertCreated()->assertJsonPath('data.version', 2);
        $this->actingAs($this->user())->getJson('/api/v1/cvs/'.$id)->assertForbidden();
        $this->assertDatabaseHas('cvs', ['id' => $id, 'status' => 'approved']);
    }

    public function test_cv_rejects_spoofed_and_oversize_uploads(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user())->postJson('/api/v1/cvs', ['title' => 'Spoof', 'source' => 'uploaded', 'file' => UploadedFile::fake()->createWithContent('fake.pdf', '<?php echo 1;')])->assertUnprocessable();
        $this->postJson('/api/v1/cvs', ['title' => 'Large', 'source' => 'uploaded', 'file' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')])->assertUnprocessable();
    }

    public function test_opportunity_approval_and_company_isolation(): void
    {
        $o = $this->opportunity();
        $o->update(['status' => 'pending']);
        $s = $this->user();
        $this->actingAs($s)->getJson('/api/v1/opportunities')->assertJsonCount(0, 'data');
        $this->postJson('/api/v1/opportunities/'.$o->id.'/review', ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($this->user('admin'))->postJson('/api/v1/opportunities/'.$o->id.'/review', ['decision' => 'rejected'])->assertUnprocessable();
        $this->postJson('/api/v1/opportunities/'.$o->id.'/review', ['decision' => 'approved'])->assertOk();
        $this->actingAs($s)->getJson('/api/v1/opportunities')->assertJsonCount(1, 'data');
        $this->putJson('/api/v1/opportunities/'.$o->id.'/save', ['saved' => true])->assertOk();
        $this->putJson('/api/v1/opportunities/'.$o->id.'/save', ['saved' => true])->assertOk();
        $this->assertDatabaseCount('saved_opportunities', 1);
        $r = $this->user('recruiter');
        $r->update(['company_id' => $this->opportunity()->company_id]);
        $this->actingAs($r)->getJson('/api/v1/opportunities')->assertJsonCount(1, 'data');
    }

    public function test_workflow_all_legal_transitions_and_history(): void
    {
        foreach (ApplicationWorkflow::NEXT as $from => $targets) {
            foreach ($targets as $to) {
                $a = $this->application($from);
                $actor = match (true) {
                    in_array($to, ['submitted', 'offer_accepted', 'offer_declined']) => $a->user,in_array($to, ['corrections_requested', 'rejected_by_university', 'forwarded']) => $this->user('admin'),default => $this->user('recruiter')
                };
                if ($actor->role->value === 'recruiter') {
                    $actor->update(['company_id' => $a->opportunity->company_id]);
                }(new ApplicationWorkflow)->transition($a, $to, $actor, 'Reason');
                $this->assertDatabaseHas('application_status_history', ['application_id' => $a->id, 'from_status' => $from, 'to_status' => $to]);
            }
        }
    }

    public function test_every_illegal_transition_is_rejected(): void
    {
        $states = array_unique(array_merge(['withdrawn'], array_keys(ApplicationWorkflow::NEXT), ...array_values(ApplicationWorkflow::NEXT)));
        foreach ($states as $from) {
            foreach ($states as $to) {
                $legal = in_array($to, ApplicationWorkflow::NEXT[$from] ?? []) || ($to === 'withdrawn' && in_array($from, ['submitted', 'corrections_requested', 'forwarded', 'shortlisted', 'interview']));
                if ($legal) {
                    continue;
                }$a = $this->application($from);
                try {
                    (new ApplicationWorkflow)->transition($a, $to, $this->user('admin'), 'Reason');
                    $this->fail("Illegal $from → $to accepted");
                } catch (HttpException $e) {
                    $this->assertSame(422, $e->getStatusCode());
                }
            }
        }
    }

    public function test_recruiter_cannot_see_unforwarded_or_other_company_candidates(): void
    {
        $a = $this->application();
        $r = $this->user('recruiter');
        $r->update(['company_id' => $a->opportunity->company_id]);
        $this->actingAs($r)->getJson('/api/v1/applications/'.$a->id)->assertForbidden();
        $this->getJson('/api/v1/cvs/'.$a->cv_id)->assertForbidden();
        $this->actingAs($this->user('admin'))->patchJson('/api/v1/applications/'.$a->id, ['status' => 'forwarded'])->assertOk();
        $this->actingAs($r)->getJson('/api/v1/applications/'.$a->id)->assertOk();
        $other = $this->user('recruiter');
        $other->update(['company_id' => $this->opportunity()->company_id]);
        $this->actingAs($other)->getJson('/api/v1/applications/'.$a->id)->assertForbidden();
    }

    public function test_interview_offer_acceptance_creates_one_placement(): void
    {
        $a = $this->application('shortlisted');
        $r = $this->user('recruiter');
        $r->update(['company_id' => $a->opportunity->company_id]);
        $this->actingAs($r)->postJson('/api/v1/applications/'.$a->id.'/interview', ['scheduled_at' => now()->addDay()->toISOString(), 'duration_min' => 45, 'type' => 'Technical', 'mode' => 'Online', 'interviewer' => 'Demo interviewer', 'link_or_location' => 'Meeting room'])->assertOk();
        $this->postJson('/api/v1/applications/'.$a->id.'/offer', ['details' => 'Fictional offer', 'expires_at' => now()->addDays(5)->toISOString(), 'start_date' => now()->addDays(10)->toDateString()])->assertOk();
        $id = DB::table('offers')->value('id');
        $this->actingAs($this->user())->postJson('/api/v1/offers/'.$id.'/respond', ['decision' => 'accepted'])->assertForbidden();
        $this->actingAs($a->user)->postJson('/api/v1/offers/'.$id.'/respond', ['decision' => 'accepted'])->assertOk();
        $this->postJson('/api/v1/offers/'.$id.'/respond', ['decision' => 'accepted'])->assertUnprocessable();
        $this->assertDatabaseCount('placement_records', 1);
    }

    public function test_expired_offer_cannot_be_accepted_and_offer_cannot_be_withdrawn(): void
    {
        $a = $this->application('offer');
        $id = DB::table('offers')->insertGetId(['application_id' => $a->id, 'details' => 'Offer', 'expires_at' => now()->subDay(), 'status' => 'pending', 'start_date' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($a->user)->postJson('/api/v1/offers/'.$id.'/respond', ['decision' => 'accepted'])->assertUnprocessable();
        $this->patchJson('/api/v1/applications/'.$a->id, ['status' => 'withdrawn'])->assertUnprocessable();
    }

    public function test_legacy_mutations_cannot_bypass_workflow(): void
    {
        $this->actingAs($this->user('admin'))->postJson('/api/applications', [])->assertNotFound();
        $this->postJson('/api/opportunities', [])->assertStatus(405);
    }

    public function test_each_eligibility_gate_and_duplicate_submission(): void
    {
        $this->seedDemoFixtures();
        $s = User::where('email', 'stage1@demo.test')->first();
        $o = $this->opportunity();
        $cv = Cv::where('user_id', $s->id)->first();
        $service = new ApplicationWorkflow;
        $baseline = $service->eligibility($s, $o, $cv);
        $this->assertNotContains(false, array_map('boolval', $baseline));
        $s->phone = null;
        $this->assertFalse($service->eligibility($s, $o, $cv)['Profile complete']);
        $s->refresh();
        $s->profile->verification_status = 'pending';
        $this->assertFalse($service->eligibility($s, $o, $cv)['Student verified']);
        $s->unsetRelation('profile');
        $o->min_cgpa = 4;
        $this->assertFalse($service->eligibility($s, $o, $cv)['Minimum CGPA met']);
        $o->min_cgpa = null;
        $o->deadline = now()->subDay();
        $this->assertFalse($service->eligibility($s, $o, $cv)['Opportunity open']);
        $o->refresh();
        $cv->status = 'draft';
        $this->assertFalse($service->eligibility($s, $o, $cv)['Approved CV selected']);
        $cv->refresh();
        $review = DB::table('cv_reviews')->where('cv_id', $cv->id);
        $review->update(['placement_recommendation' => 'not_ready']);
        $this->assertFalse($service->eligibility($s, $o, $cv)['Advisor recommends placement']);
        $review->update(['placement_recommendation' => 'ready']);
        $assignment = DB::table('advisor_assignments')->where('student_id', $s->id);
        $assignment->update(['status' => 'ended']);
        $this->assertFalse((bool) $service->eligibility($s, $o, $cv)['Active advisor assigned']);
        $assignment->update(['status' => 'active']);
        $this->actingAs($s)->postJson('/api/v1/applications', ['opportunity_id' => $o->id, 'cv_id' => $cv->id])->assertCreated()->assertJsonPath('data.workflow_status', 'submitted');
        $this->postJson('/api/v1/applications', ['opportunity_id' => $o->id, 'cv_id' => $cv->id])->assertUnprocessable();
        $this->assertFalse($service->eligibility($s, $o, $cv)['No duplicate application']);
    }

    public function test_interview_overlap_and_feedback_lifecycle(): void
    {
        $a = $this->application('shortlisted');
        $r = $this->user('recruiter');
        $r->update(['company_id' => $a->opportunity->company_id]);
        $other = Application::create(['user_id' => $a->user_id, 'student_id' => $a->user_id, 'opportunity_id' => $this->opportunity()->id, 'cv_id' => $a->cv_id, 'status' => 'interview', 'workflow_status' => 'interview', 'forwarded_at' => now()]);
        DB::table('interviews')->insert(['application_id' => $other->id, 'scheduled_at' => now()->addDay(), 'duration_min' => 60, 'mode' => 'Online', 'created_at' => now(), 'updated_at' => now()]);
        $data = ['scheduled_at' => now()->addDay()->addMinutes(30)->toISOString(), 'duration_min' => 30, 'type' => 'Technical', 'mode' => 'Online', 'interviewer' => 'Demo', 'link_or_location' => 'Demo room'];
        $this->actingAs($r)->postJson('/api/v1/applications/'.$a->id.'/interview', $data)->assertUnprocessable();
        $data['scheduled_at'] = now()->addDays(2)->toISOString();
        $this->postJson('/api/v1/applications/'.$a->id.'/interview', $data)->assertOk();
        $id = DB::table('interviews')->where('application_id', $a->id)->value('id');
        $this->patchJson('/api/v1/interviews/'.$id, ['rating' => 5, 'recommendation' => 'hire', 'comments' => 'Good'])->assertUnprocessable();
        DB::table('interviews')->where('id', $id)->update(['scheduled_at' => now()->subDay()]);
        $this->patchJson('/api/v1/interviews/'.$id, ['rating' => 5, 'recommendation' => 'hire', 'comments' => 'Good'])->assertOk();
        $this->assertDatabaseHas('interviews', ['id' => $id, 'status' => 'completed']);
    }

    public function test_workflow_lists_and_student_profiles_enforce_roles(): void
    {
        $s = $this->user();
        $a = $this->advisor();
        $this->assign($s, $a);
        foreach ([$s, $a, $this->user('admin')] as $u) {
            $this->actingAs($u)->getJson('/api/v1/sessions')->assertOk();
        }
        $this->actingAs($a)->getJson('/api/v1/students/'.$s->id)->assertOk();
        $this->actingAs($this->advisor())->getJson('/api/v1/students/'.$s->id)->assertForbidden();
        $this->actingAs($this->user('recruiter'))->getJson('/api/v1/sessions')->assertForbidden();
        $this->getJson('/api/v1/allocations')->assertForbidden();
        $this->getJson('/api/v1/placements')->assertForbidden();
        $this->actingAs($this->user('admin'))->getJson('/api/v1/allocations')->assertOk();
        $this->getJson('/api/v1/placements')->assertOk();
    }

    public function test_advisor_availability_capacity_cannot_drop_below_workload(): void
    {
        $a = $this->advisor();
        $this->assign($this->user(), $a);
        $this->assign($this->user(), $a);
        $data = ['title' => 'Career coach', 'bio' => 'Fictional coach', 'capacity' => 1, 'is_accepting' => true, 'expertise' => ['Software']];
        $this->actingAs($a)->putJson('/api/v1/advisor-profile', $data)->assertUnprocessable();
        $data['capacity'] = 5;
        $this->putJson('/api/v1/advisor-profile', $data)->assertOk();
        $this->actingAs($this->user())->putJson('/api/v1/advisor-profile', $data)->assertForbidden();
    }

    public function test_advisor_interests_are_saved_and_shown_to_students(): void
    {
        $advisor = $this->advisor();
        $data = [
            'title' => 'Career coach',
            'bio' => 'Fictional coach',
            'capacity' => 20,
            'is_accepting' => true,
            'expertise' => ['Software'],
            'interests' => ['AI careers', 'Career planning'],
        ];

        $this->actingAs($advisor)->putJson('/api/v1/advisor-profile', $data)
            ->assertOk()->assertJsonPath('interests.0', 'AI careers');
        $this->assertDatabaseHas('advisor_interests', ['advisor_id' => $advisor->id, 'tag' => 'AI careers']);

        $this->actingAs($this->user())->getJson('/api/v1/advisors')
            ->assertOk()->assertJsonPath('data.0.interests.1', 'Career planning');
    }

    public function test_expired_opportunity_is_closed_by_command(): void
    {
        $o = $this->opportunity();
        $o->update(['deadline' => now()->subDays(2)]);
        $this->artisan('ccp:close-opportunities')->assertSuccessful();
        $this->assertDatabaseHas('opportunities', ['id' => $o->id, 'status' => 'closed']);
    }

    public function test_valid_phone_can_be_saved_without_affecting_other_accounts(): void
    {
        $u = $this->user();
        StudentProfile::create(['user_id' => $u->id]);
        $this->actingAs($u)->patchJson('/api/v1/profile/steps/1', ['phone' => '+0000000000', 'advance' => true])->assertOk();
        $this->assertDatabaseHas('users', ['id' => $u->id, 'phone' => '+0000000000']);
    }

    public function test_reallocated_advisor_never_sees_previous_private_notes(): void
    {
        $s = $this->user();
        $old = $this->advisor();
        $new = $this->advisor();
        $this->assign($s, $old);
        $this->actingAs($old)->postJson('/api/notes', ['student_id' => $s->id, 'body' => 'Private old advisor note'])->assertCreated();
        $this->actingAs($s)->postJson('/api/notes', ['body' => 'Student shared note'])->assertCreated();
        $this->assign($s, $new);
        $this->actingAs($new)->getJson('/api/notes?student_id='.$s->id)->assertOk()->assertJsonCount(1)->assertJsonPath('0.body', 'Student shared note');
        $this->actingAs($old)->getJson('/api/notes?student_id='.$s->id)->assertForbidden();
        $this->actingAs($s)->getJson('/api/notes')->assertOk()->assertJsonCount(1);
    }

    public function test_request_parameters_and_numeric_routes_fail_safely(): void
    {
        $s = $this->user();
        foreach (['/api/v1/cvs?per_page=51', '/api/v1/applications?page=0', '/api/v1/opportunities?sort=unknown', '/api/v1/sessions?page[]=1'] as $url) {
            $this->actingAs($s)->getJson($url)->assertUnprocessable();
        }
        $this->actingAs($s)->getJson('/api/v1/plans/not-a-number')->assertNotFound();
        $this->getJson('/api/v1/cvs/not-a-number')->assertNotFound();
    }

    public function test_workflow_policy_role_matrix_is_explicit_and_suspension_blocks_every_action(): void
    {
        foreach (['student', 'advisor', 'admin', 'recruiter'] as $role) {
            $u = $this->user($role);
            StudentProfile::create(['user_id' => $u->id, 'verification_status' => 'verified']);
            foreach (WorkflowPolicy::ROLES as $controller => $actions) {
                foreach ($actions as $action => $roles) {
                    $this->assertSame(in_array($role, $roles), Gate::forUser($u)->allows('workflow', [$controller, $action]), $role.' '.$controller.'@'.$action);
                    $u->status = 'suspended';
                    $this->assertFalse(Gate::forUser($u)->allows('workflow', [$controller, $action]));
                    $u->status = 'active';
                }
            }
        }
    }

    public function test_session_requires_confirmation_before_feedback_and_emits_activity(): void
    {
        $s = $this->user();
        $a = $this->advisor();
        $this->assign($s, $a);
        $res = $this->actingAs($s)->postJson('/api/v1/sessions', ['type' => 'Career planning', 'scheduled_at' => now()->addDay()->toIso8601String(), 'mode' => 'Online'])->assertOk();
        $id = $res->json('data.id');
        $this->assertDatabaseHas('alerts', ['user_id' => $a->id, 'title' => 'Counselling session: requested']);
        $this->actingAs($a)->patchJson('/api/v1/sessions/'.$id, [])->assertUnprocessable();
        DB::table('counselling_sessions')->where('id', $id)->update(['scheduled_at' => now()->subHours(2), 'ends_at' => now()->subHour()]);
        $data = ['action' => 'complete', 'body' => 'Reviewed goals', 'next_step' => 'Prepare CV'];
        $this->patchJson('/api/v1/sessions/'.$id, $data)->assertUnprocessable();
        DB::table('counselling_sessions')->where('id', $id)->update(['status' => 'confirmed']);
        $this->patchJson('/api/v1/sessions/'.$id, $data)->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertDatabaseHas('audit_logs', ['action' => 'session.complete', 'entity_id' => $id]);
    }

    public function test_admin_queue_filters_and_offers_include_answered_history(): void
    {
        $pending = $this->application();
        $correction = $this->application('corrections_requested');
        $forward = $this->application('forwarded');
        $this->actingAs($this->user('admin'))->getJson('/api/v1/applications?review=pending')->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.id', $pending->id);
        $this->getJson('/api/v1/applications?review=corrections')->assertOk()->assertJsonPath('data.data.0.id', $correction->id);
        $this->getJson('/api/v1/applications?review=forwarded')->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.id', $forward->id);
        $forward->update(['workflow_status' => 'offer_accepted']);
        DB::table('offers')->insert(['application_id' => $forward->id, 'details' => 'Accepted offer', 'expires_at' => now()->addDay(), 'status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs(User::find($forward->user_id))->getJson('/api/v1/applications?offers=1')->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.workflow_status', 'offer_accepted');
        $this->actingAs(User::find($pending->user_id))->getJson('/api/v1/applications?offers=1')->assertOk()->assertJsonCount(0, 'data.data');
    }

    public function test_applications_for_soft_deleted_students_are_hidden_and_not_openable(): void
    {
        $application = $this->application();
        $student = User::findOrFail($application->user_id);
        DB::table('interviews')->insert([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $student->delete();

        $this->actingAs($this->user('admin'))->getJson('/api/v1/applications')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
        $this->getJson('/api/v1/applications/'.$application->id)->assertNotFound();
        $this->getJson('/api/v1/interviews')->assertOk()->assertJsonCount(0, 'data.data');
    }

    public function test_admin_dashboard_review_and_offer_counts_exclude_soft_deleted_students(): void
    {
        $visibleOffer = $this->application('offer');
        $hiddenOffer = $this->application('offer');
        User::findOrFail($hiddenOffer->user_id)->delete();

        $this->actingAs($this->user('admin'))->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.1.value', 1)
            ->assertJsonPath('stats.3.value', 1);
        $this->assertNotEquals($visibleOffer->user_id, $hiddenOffer->user_id);
    }

    public function test_allocation_pagination_keeps_both_preferences_together(): void
    {
        $a = $this->advisor();
        $b = $this->advisor();
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->user())->postJson('/api/v1/advisor-preferences', ['first' => $a->id, 'second' => $b->id])->assertOk();
        }
        $this->actingAs($this->user('admin'))->getJson('/api/v1/allocations?per_page=1&page=2')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 3)->assertJsonPath('meta.last_page', 3);
    }
}
