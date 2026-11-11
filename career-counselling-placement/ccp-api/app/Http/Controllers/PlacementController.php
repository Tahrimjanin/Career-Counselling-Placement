<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowRequest as Request;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\ProfileResource;
use App\Models\Application;
use App\Models\Cv;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use App\Services\WorkflowActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlacementController extends Controller
{
    private function access(Request $r, Application $a): void
    {
        $this->authorize('candidate-record', $a);
        abort_unless($a->user()->exists(), 404, 'The applicant account is no longer available.');
    }

    public function index(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $u = $r->user();
        $q = Application::whereHas('user')->with('user:id,name,email', 'opportunity.company:id,name', 'cv:id,version,status,reviewed_by,reviewed_at', 'interview');
        if ($u->role->value === 'student') {
            $q->where('user_id', $u->id);
        } elseif ($u->role->value === 'recruiter') {
            $q->forCompany($u->company_id);
            abort_unless($u->company_id, 403);
        } else {
            abort_unless($u->role->value === 'admin', 403);
        }

        if ($r->boolean('offers')) {
            $q->whereIn('id', DB::table('offers')->select('application_id'));
        }
        if ($r->filled('status')) {
            $q->where('workflow_status', $r->status);
        }
        if ($u->role->value === 'admin') {
            if ($r->review === 'pending') {
                $q->where('workflow_status', 'submitted');
            }
            if ($r->review === 'corrections') {
                $q->where('workflow_status', 'corrections_requested');
            }
            if ($r->review === 'forwarded') {
                $q->whereNotNull('forwarded_at')->where('forwarded_at', '>=', now()->startOfWeek());
            }
        }
        if ($r->filled('q')) {
            $q->whereHas('opportunity', fn ($w) => $w->where('title', 'like', '%'.$r->q.'%'));
        }

        return ['data' => $q->orderByDesc('id')->paginate($r->integer('per_page', 20))->through(fn ($row) => (new ApplicationResource($row))->resolve())];
    }

    public function detail(Request $r, Application $application)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $application);

        return ['data' => new ApplicationResource($application->load('user:id,name,email', 'opportunity.company:id,name', 'cv:id,version,status,reviewed_by,reviewed_at')), 'candidate' => new ProfileResource(User::with('profile')->findOrFail($application->user_id)), 'history' => DB::table('application_status_history')->where('application_id', $application->id)->orderBy('id')->get(), 'offer' => DB::table('offers')->where('application_id', $application->id)->first()];
    }

    public function eligibility(Request $r, Opportunity $opportunity)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'student', 403);

        return ['data' => (new ApplicationWorkflow)->eligibility($r->user(), $opportunity, Cv::find($r->query('cv_id')))];
    }

    public function apply(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'student', 403);
        abort_unless($r->user()->profile?->verification_status === 'verified', 403, 'Student verification is required.');
        $d = $r->validated();

        return DB::transaction(function () use ($r, $d) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->first();
            $o = Opportunity::whereKey($d['opportunity_id'])->lockForUpdate()->first();
            $cv = Cv::whereKey($d['cv_id'])->lockForUpdate()->first();
            $w = new ApplicationWorkflow;
            $checks = $w->eligibility($u, $o, $cv);
            abort_if(in_array(false, array_map('boolval', $checks), true), 422, implode('; ', array_keys(array_filter($checks, fn ($v) => ! $v))));
            $a = Application::create($d + ['user_id' => $u->id, 'student_id' => $u->id, 'workflow_status' => 'submitted', 'status' => 'submitted', 'eligibility_snapshot' => json_encode($checks), 'submitted_at' => now()]);
            $w->history($a, null, 'submitted', $u->id);

            return response()->json(['data' => $a], 201);
        });
    }

    public function change(Request $r, Application $application)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $application);
        $d = $r->validated();
        abort_if(in_array($d['status'], ['offer', 'offer_accepted', 'offer_declined', 'interview']), 422, 'Use the interview or offer action.');

        return DB::transaction(function () use ($r, $application, $d) {
            $a = Application::whereKey($application->id)->lockForUpdate()->first();
            if ($d['status'] === 'submitted') {
                abort_unless($r->user()->id === $a->user_id, 403);
                $checks = (new ApplicationWorkflow)->eligibility($r->user(), $a->opportunity, $a->cv);
                unset($checks['No duplicate application']);
                $checks['Approved CV selected'] = in_array($a->cv->status, ['approved', 'superseded']) && $a->cv->reviewed_at !== null;
                abort_if(in_array(false, array_map('boolval', $checks), true), 422, 'Eligibility requirements are not met.');
                $a->update(['cover_note' => $d['cover_note'] ?? $a->cover_note]);
            }

            return ['data' => (new ApplicationWorkflow)->transition($a, $d['status'], $r->user(), $d['reason'] ?? null)];
        });
    }

    public function interviews(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $u = $r->user();
        $q = DB::table('interviews')->join('applications', 'applications.id', '=', 'interviews.application_id')->join('opportunities', 'opportunities.id', '=', 'applications.opportunity_id')->join('users', 'users.id', '=', 'applications.user_id')->whereNull('users.deleted_at')->select('interviews.*', 'users.name as student_name', 'opportunities.title');
        if ($u->role->value === 'student') {
            $q->where('applications.user_id', $u->id);
        } elseif ($u->role->value === 'recruiter') {
            abort_unless($u->company_id, 403);
            $q->where('opportunities.company_id', $u->company_id)->whereNotNull('applications.forwarded_at');
        } else {
            abort_unless($u->role->value === 'admin', 403);
        }
        $rows = $q->orderBy('scheduled_at')->paginate($r->integer('per_page', 20));
        foreach ($rows as $row) {
            $row->feedback = DB::table('interview_feedback')->where('interview_id', $row->id)->first();
        }

        return ['data' => $rows];
    }

    public function interview(Request $r, Application $application)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $application);
        abort_unless($r->user()->role->value === 'recruiter', 403);
        $d = $r->validated();

        return DB::transaction(function () use ($r, $application, $d) {
            User::whereKey($application->user_id)->lockForUpdate()->first();
            $a = Application::whereKey($application->id)->lockForUpdate()->first();
            abort_unless(in_array($a->workflow_status, ['shortlisted', 'interview']), 422, 'Shortlist this candidate first.');
            $start = Carbon::parse($d['scheduled_at'])->utc();
            $end = $start->copy()->addMinutes($d['duration_min']);
            $others = DB::table('interviews')->join('applications', 'applications.id', '=', 'interviews.application_id')->where('applications.user_id', $a->user_id)->where('interviews.application_id', '!=', $a->id)->where('interviews.status', 'scheduled')->select('interviews.*')->get();
            foreach ($others as $i) {
                abort_if(Carbon::parse($i->scheduled_at)->lt($end) && Carbon::parse($i->scheduled_at)->addMinutes($i->duration_min)->gt($start), 422, 'Candidate has an overlapping interview.');
            }
            $d['scheduled_at'] = $start;
            DB::table('interviews')->updateOrInsert(['application_id' => $a->id], $d + ['scheduled_by' => $r->user()->id, 'status' => 'scheduled', 'created_at' => now(), 'updated_at' => now()]);
            if ($a->workflow_status === 'shortlisted') {
                (new ApplicationWorkflow)->transition($a, 'interview', $r->user());
            }

            WorkflowActivity::notify([$a->user_id, $r->user()->id], 'Interview scheduled', $start->toIso8601String(), 'interviews', '/interviews');
            WorkflowActivity::audit($r->user()->id, 'interview.scheduled', 'application', $a->id, $d);

            return ['message' => 'Interview scheduled.'];
        });
    }

    public function feedback(Request $r, int $id)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);

        return DB::transaction(function () use ($r, $id) {
            $i = DB::table('interviews')->where('id', $id)->lockForUpdate()->first();
            abort_unless($i, 404);
            $a = Application::findOrFail($i->application_id);
            $this->access($r, $a);
            abort_unless($r->user()->role->value === 'recruiter', 403);
            $d = $r->validated();
            abort_unless($i->status === 'scheduled', 422, 'Interview is already closed.');
            if (($d['action'] ?? '') === 'cancel') {
                abort_if(Carbon::parse($i->scheduled_at)->isPast(), 422, 'Interview has already started.');
                DB::table('interviews')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            } else {
                abort_if(Carbon::parse($i->scheduled_at)->addMinutes($i->duration_min)->isFuture(), 422, 'Record feedback after the interview ends.');
                DB::transaction(function () use ($r, $id, $d) {
                    DB::table('interview_feedback')->updateOrInsert(['interview_id' => $id], collect($d)->except('action')->all() + ['recruiter_id' => $r->user()->id, 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('interviews')->where('id', $id)->update(['status' => 'completed', 'updated_at' => now()]);
                });
            }

            WorkflowActivity::notify([$a->user_id], 'Interview '.(($d['action'] ?? '') === 'cancel' ? 'cancelled' : 'completed'), null, 'interviews', '/interviews');
            WorkflowActivity::audit($r->user()->id, 'interview.updated', 'interview', $id, ['action' => $d['action'] ?? 'feedback']);

            return ['message' => 'Interview updated.'];
        });
    }

    public function offer(Request $r, Application $application)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $application);
        abort_unless($r->user()->role->value === 'recruiter', 403);
        $d = $r->validated();
        $d['expires_at'] = Carbon::parse($d['expires_at'])->utc();

        return DB::transaction(function () use ($r, $application, $d) {
            $a = Application::whereKey($application->id)->lockForUpdate()->first();
            $existing = DB::table('offers')->where('application_id', $a->id)->first();
            if ($existing) {
                abort_unless($existing->status === 'pending' && $a->workflow_status === 'offer', 422, 'This offer can no longer be edited.');
                DB::table('offers')->where('id', $existing->id)->update($d + ['updated_at' => now()]);
            } else {
                (new ApplicationWorkflow)->transition($a, 'offer', $r->user());
                DB::table('offers')->insert($d + ['application_id' => $a->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            }

            WorkflowActivity::notify([$a->user_id], 'Offer ready for review', 'Open your offers workspace to review the details.', 'general', '/offers');
            WorkflowActivity::audit($r->user()->id, 'offer.sent', 'application', $a->id);

            return ['message' => 'Offer sent.'];
        });
    }

    public function respond(Request $r, int $id)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $d = $r->validated();

        return DB::transaction(function () use ($r, $id, $d) {
            $offer = DB::table('offers')->where('id', $id)->lockForUpdate()->first();
            abort_unless($offer, 404);
            $a = Application::whereKey($offer->application_id)->lockForUpdate()->first();
            abort_unless($r->user()->role->value === 'student' && $a->user_id === $r->user()->id, 403);
            abort_unless($offer->status === 'pending' && Carbon::parse($offer->expires_at)->isFuture(), 422, 'Offer expired or already answered.');
            abort_if($d['decision'] === 'accepted' && ! $offer->start_date, 422, 'This legacy offer has no start date. Ask the recruiter to update the offer.');
            (new ApplicationWorkflow)->transition($a, 'offer_'.$d['decision'], $r->user());
            DB::table('offers')->where('id', $id)->update(['status' => $d['decision'], 'responded_at' => now(), 'updated_at' => now()]);
            if ($d['decision'] === 'accepted') {
                DB::table('placement_records')->insert(['application_id' => $a->id, 'student_id' => $a->user_id, 'company_id' => $a->opportunity->company_id, 'role_title' => $a->opportunity->title, 'job_type' => $a->opportunity->type, 'start_date' => $offer->start_date, 'recorded_by' => $r->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            }

            return ['message' => 'Offer '.$d['decision'].'.'];
        });
    }

    public function placements(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'admin', 403);

        return ['data' => DB::table('placement_records')->join('users', 'users.id', '=', 'placement_records.student_id')->join('companies', 'companies.id', '=', 'placement_records.company_id')->select('placement_records.*', 'users.name as student_name', 'companies.name as company_name')->orderByDesc('placement_records.id')->paginate($r->integer('per_page', 20))];
    }
}
