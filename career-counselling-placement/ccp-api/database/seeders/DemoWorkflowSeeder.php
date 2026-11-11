<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ProfileCompletion;
use Illuminate\Support\Facades\DB;

class DemoWorkflowSeeder extends DemoSeeder
{
    public function run(): void
    {
        $this->ensureDemoEnvironment();
        $advisor = User::where('email', 'advisor@demo.test')->firstOrFail();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $o = Opportunity::where('title', 'Demo Job opportunity')->firstOrFail();
        $stages = ['submitted', 'corrections_requested', 'rejected_by_university', 'forwarded', 'shortlisted', 'interview', 'offer', 'offer_accepted', 'offer_declined', 'rejected_by_company', 'withdrawn'];
        foreach ($stages as $i => $stage) {
            $u = User::firstOrCreate(['email' => 'stage'.($i + 1).'@demo.test'], ['name' => 'Demo Stage '.($i + 1), 'password' => 'DemoPass123!', 'role' => 'student', 'email_verified_at' => now(), 'advisor_id' => $advisor->id, 'phone' => '+0000000000', 'department' => 'CSE']);
            $p = StudentProfile::firstOrCreate(['user_id' => $u->id], ['student_no' => 'DEMO-'.($i + 1), 'department' => 'CSE', 'batch' => 'Demo 2026', 'cgpa' => 3.5, 'ssc_institution' => 'Demo School', 'ssc_board' => 'Demo Board', 'ssc_year' => 2019, 'ssc_gpa' => 4.5, 'hsc_institution' => 'Demo College', 'hsc_board' => 'Demo Board', 'hsc_year' => 2021, 'hsc_gpa' => 4.5, 'career_objective' => 'Develop useful software.', 'interests' => ['Software engineering'], 'has_experience' => false, 'has_certificates' => false, 'wizard_step' => 8, 'verification_status' => 'verified', 'verified_by' => $admin->id, 'verified_at' => now()]);
            DB::table('advisor_assignments')->updateOrInsert(['student_id' => $u->id, 'status' => 'active'], ['advisor_id' => $advisor->id, 'assigned_by' => $admin->id, 'started_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            foreach (DB::table('skills')->whereIn('name', ['React', 'Communication'])->pluck('id') as $id) {
                DB::table('student_skill')->updateOrInsert(['user_id' => $u->id, 'skill_id' => $id], ['created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('cvs')->updateOrInsert(['user_id' => $u->id, 'version' => 1], ['title' => 'Demo structured CV', 'path' => '', 'source' => 'builder', 'content' => json_encode(['name' => $u->name, 'email' => $u->email, 'summary' => 'Fictional CV fixture.', 'education' => 'BSc in Computer Science', 'skills' => 'React, Communication', 'experience' => 'Academic portfolio']), 'status' => 'approved', 'reviewed_by' => $advisor->id, 'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $cv = DB::table('cvs')->where('user_id', $u->id)->where('version', 1)->value('id');
            DB::table('cv_reviews')->updateOrInsert(['cv_id' => $cv, 'advisor_id' => $advisor->id], ['decision' => 'approved', 'strengths' => 'Clear structure', 'improvements' => 'Continue practising', 'placement_recommendation' => 'ready', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('career_plans')->updateOrInsert(['student_id' => $u->id], ['advisor_id' => $advisor->id, 'target_role' => 'Software developer', 'created_at' => now(), 'updated_at' => now()]);
            $plan = DB::table('career_plans')->where('student_id', $u->id)->value('id');
            DB::table('plan_milestones')->updateOrInsert(['plan_id' => $plan, 'title' => 'Prepare portfolio'], ['status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);
            $forwarded = ! in_array($stage, ['submitted', 'corrections_requested', 'rejected_by_university']);
            $legacy = match ($stage) {
                'shortlisted' => 'Shortlisted','interview' => 'Interview','offer','offer_accepted','offer_declined' => 'Offer','rejected_by_company','rejected_by_university' => 'Rejected',default => 'Applied'
            };
            DB::table('applications')->updateOrInsert(['user_id' => $u->id, 'opportunity_id' => $o->id], ['student_id' => $u->id, 'cv_id' => $cv, 'status' => $legacy, 'workflow_status' => $stage, 'admin_review' => $forwarded ? 'approved' : ($stage === 'rejected_by_university' ? 'rejected' : 'pending'), 'submitted_at' => now(), 'forwarded_at' => $forwarded ? now() : null, 'forwarded_by' => $forwarded ? $admin->id : null, 'created_at' => now(), 'updated_at' => now()]);
            $app = DB::table('applications')->where('user_id', $u->id)->where('opportunity_id', $o->id)->value('id');
            DB::table('application_status_history')->updateOrInsert(['application_id' => $app, 'to_status' => $stage], ['actor_id' => $admin->id, 'reason' => 'Demo fixture, not a live workflow event.', 'created_at' => now(), 'updated_at' => now()]);
            if (in_array($stage, ['interview', 'offer', 'offer_accepted', 'offer_declined'])) {
                DB::table('interviews')->updateOrInsert(['application_id' => $app], ['scheduled_at' => now()->addDays(5), 'mode' => 'On-site', 'link_or_location' => 'Demo office', 'created_at' => now(), 'updated_at' => now()]);
            }
            if (in_array($stage, ['offer', 'offer_accepted', 'offer_declined'])) {
                DB::table('offers')->updateOrInsert(['application_id' => $app], ['details' => 'Fictional offer fixture.', 'start_date' => now()->addDays(30)->toDateString(), 'expires_at' => now()->addDays(10), 'status' => match ($stage) {
                    'offer_accepted' => 'accepted','offer_declined' => 'declined',default => 'pending'
                }, 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($stage === 'offer_accepted') {
                DB::table('placement_records')->updateOrInsert(['application_id' => $app], ['student_id' => $u->id, 'company_id' => $o->company_id, 'role_title' => 'Demo Developer', 'job_type' => 'Job', 'start_date' => now()->addDays(30)->toDateString(), 'recorded_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            $p->update(['completion_percent' => app(ProfileCompletion::class)->calculate($u->load('profile'))['percent']]);
        }
        DB::table('settings')->updateOrInsert(['key' => 'demo_mode'], ['value' => 'true', 'created_at' => now(), 'updated_at' => now()]);

    }
}
