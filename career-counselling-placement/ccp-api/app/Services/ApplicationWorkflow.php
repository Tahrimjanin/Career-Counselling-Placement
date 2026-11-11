<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Cv;
use App\Models\Opportunity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApplicationWorkflow
{
    public const NEXT = ['submitted' => ['corrections_requested', 'rejected_by_university', 'forwarded'], 'corrections_requested' => ['submitted'], 'forwarded' => ['shortlisted', 'rejected_by_company'], 'shortlisted' => ['interview', 'rejected_by_company'], 'interview' => ['offer', 'rejected_by_company'], 'offer' => ['offer_accepted', 'offer_declined', 'rejected_by_company']];

    public function eligibility(User $u, Opportunity $o, ?Cv $cv): array
    {
        $assignment = DB::table('advisor_assignments')->where('student_id', $u->id)->where('status', 'active')->first();
        $recommendation = $assignment ? DB::table('cv_reviews')->join('cvs', 'cvs.id', '=', 'cv_reviews.cv_id')->where('cvs.user_id', $u->id)->where('advisor_id', $assignment->advisor_id)->whereNotNull('placement_recommendation')->orderByDesc('cv_reviews.id')->value('placement_recommendation') : null;

        return ['Profile complete' => (new ProfileCompletion)->calculate($u)['percent'] === 100, 'Student verified' => $u->profile?->verification_status === 'verified', 'Active advisor assigned' => $assignment && User::whereKey($assignment->advisor_id)->where('status', 'active')->exists(), 'Approved CV selected' => $cv && $cv->user_id === $u->id && $cv->status === 'approved' && ! $cv->deleted_at, 'Advisor recommends placement' => $recommendation === 'ready', 'Opportunity open' => $o->status === 'open' && $o->deadline && Carbon::parse($o->deadline->toDateString(), 'Asia/Dhaka')->endOfDay()->isFuture(), 'Minimum CGPA met' => ! $o->min_cgpa || ($u->profile?->cgpa !== null && $u->profile->cgpa >= $o->min_cgpa), 'No duplicate application' => ! Application::where('user_id', $u->id)->where('opportunity_id', $o->id)->exists()];
    }

    public function transition(Application $a, string $to, User $actor, ?string $reason = null): Application
    {
        return DB::transaction(function () use ($a, $to, $actor, $reason) {
            $a = Application::whereKey($a->id)->lockForUpdate()->firstOrFail();
            $from = $a->workflow_status ?? 'submitted';
            $legal = in_array($to, self::NEXT[$from] ?? [], true) || ($to === 'withdrawn' && in_array($from, ['submitted', 'corrections_requested', 'forwarded', 'shortlisted', 'interview']));
            abort_unless($legal, 422, 'This status transition is not allowed.');
            $role = $actor->role->value;
            $student = in_array($to, ['submitted', 'withdrawn', 'offer_accepted', 'offer_declined']);
            $admin = in_array($to, ['corrections_requested', 'rejected_by_university', 'forwarded']);
            abort_unless($student ? ($role === 'student' && $actor->id === $a->user_id) : ($admin ? $role === 'admin' : ($role === 'recruiter' && $actor->company_id && $a->forwarded_at && $a->opportunity->company_id === $actor->company_id)), 403);
            abort_if(in_array($to, ['corrections_requested', 'rejected_by_university', 'rejected_by_company']) && ! trim($reason ?? ''), 422, 'A reason is required.');
            $data = ['workflow_status' => $to, 'status' => $to];
            if ($admin) {
                $data += ['admin_decision' => $to, 'admin_reason' => $reason, 'admin_reviewed_by' => $actor->id, 'admin_reviewed_at' => now(), 'admin_review' => $to === 'forwarded' ? 'approved' : ($to === 'rejected_by_university' ? 'rejected' : 'pending')];
            }
            if ($to === 'forwarded') {
                $data += ['forwarded_at' => now(), 'forwarded_by' => $actor->id];
            }if ($to === 'withdrawn') {
                $data['withdrawn_at'] = now();
            }
            $a->update($data);
            if (in_array($to, ['withdrawn', 'rejected_by_company', 'rejected_by_university', 'offer_declined'])) {
                DB::table('interviews')->where('application_id', $a->id)->where('status', 'scheduled')->update(['status' => 'cancelled', 'updated_at' => now()]);
                if ($to === 'rejected_by_company') {
                    DB::table('offers')->where('application_id', $a->id)->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);
                }
            }
            $this->history($a, $from, $to, $actor->id, $reason);

            return $a;
        });
    }

    public function history(Application $a, ?string $from, string $to, int $actor, ?string $reason = null): void
    {
        DB::table('audit_logs')->insert(['actor_id' => $actor, 'action' => 'application.'.$to, 'entity_type' => 'application', 'entity_id' => $a->id, 'old_values' => json_encode(['status' => $from]), 'new_values' => json_encode(['status' => $to, 'reason' => $reason]), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('application_status_history')->insert(['application_id' => $a->id, 'from_status' => $from, 'to_status' => $to, 'actor_id' => $actor, 'reason' => $reason, 'created_at' => now(), 'updated_at' => now()]);
        WorkflowActivity::notify([$a->user_id], 'Application: '.str_replace('_', ' ', $to), $reason, 'general', '/applications');
        $reviewers = in_array($to, ['submitted', 'corrections_requested']) ? User::where('role', 'admin')->where('status', 'active')->pluck('id')->all() : User::where('role', 'recruiter')->where('company_id', $a->opportunity->company_id)->where('status', 'active')->pluck('id')->all();
        WorkflowActivity::notify($reviewers, 'Application: '.str_replace('_', ' ', $to), $reason, 'general', in_array($to, ['submitted', 'corrections_requested']) ? '/queue' : '/candidates');
    }
}
