<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowRequest as Request;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\WorkflowActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OpportunityLifecycleController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $u = $r->user();
        $q = Opportunity::with('company:id,name')->whereNull('deleted_at');
        if (in_array($u->role->value, ['student', 'advisor'])) {
            $q->where('status', 'open')->whereDate('deadline', '>=', now('Asia/Dhaka')->toDateString());
        } elseif ($u->role->value === 'recruiter') {
            $q->where('company_id', $u->company_id);
            abort_unless($u->company_id, 403);
        }
        foreach (['type', 'work_mode', 'location'] as $k) {
            if ($r->filled($k)) {
                $q->where($k, $r->input($k));
            }
        }if ($r->filled('paid')) {
            $q->where('is_paid', $r->boolean('paid'));
        }if ($r->filled('q')) {
            $q->where(fn ($w) => $w->where('title', 'like', '%'.$r->q.'%')->orWhere('description', 'like', '%'.$r->q.'%'));
        }if ($r->boolean('saved')) {
            $q->whereIn('id', DB::table('saved_opportunities')->where('user_id', $u->id)->pluck('opportunity_id'));
        }
        $skills = DB::table('student_skill')->join('skills', 'skills.id', '=', 'skill_id')->where('user_id', $u->id)->pluck('name')->map(fn ($s) => strtolower($s))->all();
        $savedIds = DB::table('saved_opportunities')->where('user_id', $u->id)->pluck('opportunity_id')->all();
        $rows = $q->get();
        foreach ($rows as $o) {
            $required = $o->skills ?? [];
            $o->matched_skills = array_values(array_filter($required, fn ($s) => in_array(strtolower($s), $skills)));
            $o->missing_skills = array_values(array_diff($required, $o->matched_skills));
            $o->match_percent = count($required) ? (int) round(count($o->matched_skills) * 100 / count($required)) : 0;
            $o->saved = in_array($o->id, $savedIds);
        }
        if ($r->filled('skill')) {
            $rows = $rows->filter(fn ($o) => collect($o->skills)->contains(fn ($s) => str_contains(strtolower($s), strtolower($r->skill))));
        }
        $rows = match ($r->input('sort')) {
            'match' => $rows->sortByDesc('match_percent'),'deadline' => $rows->sortBy('deadline'),default => $rows->sortByDesc('id')
        };
        $page = max(1, (int) $r->input('page', 1));
        $per = min(50, max(1, (int) $r->input('per_page', 20)));

        return ['data' => OpportunityResource::collection($rows->values()->forPage($page, $per)->values()), 'meta' => ['page' => $page, 'last_page' => max(1, (int) ceil($rows->count() / $per)), 'total' => $rows->count()]];
    }

    public function create(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $u = $r->user();
        abort_unless($u->role->value === 'recruiter' && $u->company_id, 403);
        abort_unless(DB::table('companies')->where('id', $u->company_id)->where('verified', true)->exists(), 422, 'Company verification is required.');
        $d = $r->validated();

        $o = Opportunity::create($d + ['company_id' => $u->company_id, 'posted_by' => $u->id, 'status' => 'pending']);
        WorkflowActivity::notify(User::where('role', 'admin')->where('status', 'active')->pluck('id')->all(), 'Opportunity awaiting review', $o->title, 'general', '/opportunity-approval');
        WorkflowActivity::audit($u->id, 'opportunity.created', 'opportunity', $o->id);

        return (new OpportunityResource($o))->response()->setStatusCode(201);
    }

    public function review(Request $r, Opportunity $opportunity)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'admin', 403);
        $d = $r->validated();

        return DB::transaction(function () use ($r, $opportunity, $d) {
            $o = Opportunity::whereKey($opportunity->id)->lockForUpdate()->first();
            abort_unless($o->status === 'pending', 422, 'Opportunity has already been reviewed.');
            abort_if($d['decision'] === 'approved' && Carbon::parse($o->deadline->toDateString(), 'Asia/Dhaka')->endOfDay()->isPast(), 422, 'Deadline has passed.');
            $o->update(['status' => $d['decision'] === 'approved' ? 'open' : 'rejected', 'approved_by' => $r->user()->id, 'rejection_reason' => $d['reason'] ?? null]);

            WorkflowActivity::notify(User::where('company_id', $o->company_id)->pluck('id')->all(), 'Opportunity '.$d['decision'], $o->title.': '.($d['reason'] ?? 'Ready for applications'), 'general', '/post');
            WorkflowActivity::audit($r->user()->id, 'opportunity.reviewed', 'opportunity', $o->id, $d);

            return ['data' => $o];
        });
    }

    public function save(Request $r, Opportunity $opportunity)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'student', 403);
        $d = $r->validated();
        $key = ['user_id' => $r->user()->id, 'opportunity_id' => $opportunity->id];
        if ($d['saved']) {
            DB::table('saved_opportunities')->updateOrInsert($key, ['created_at' => now(), 'updated_at' => now()]);
        } else {
            DB::table('saved_opportunities')->where($key)->delete();
        }

        return ['message' => $d['saved'] ? 'Opportunity saved.' : 'Removed from saved.'];
    }
}
