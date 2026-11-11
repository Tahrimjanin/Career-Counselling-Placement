<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowRequest as Request;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Services\AdvisorAllocation;
use App\Services\WorkflowActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdvisingController extends Controller
{
    private function access(Request $r, int $id): void
    {
        $this->authorize('student-record', $id);
    }

    public function student(Request $r, int $student)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $student);

        return new ProfileResource(User::with('profile')->findOrFail($student));
    }

    public function advisorProfile(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'advisor', 403);

        return [
            'data' => DB::table('advisor_profiles')->where('user_id', $r->user()->id)->first(),
            'expertise' => DB::table('advisor_expertise')->where('advisor_id', $r->user()->id)->pluck('tag'),
            'interests' => DB::table('advisor_interests')->where('advisor_id', $r->user()->id)->pluck('tag'),
        ];
    }

    public function saveAdvisorProfile(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'advisor', 403);
        $d = $r->validated();
        DB::transaction(function () use ($r, $d) {
            User::whereKey($r->user()->id)->lockForUpdate()->first();
            abort_if(DB::table('advisor_assignments')->where('advisor_id', $r->user()->id)->where('status', 'active')->count() > $d['capacity'], 422, 'Capacity cannot be below the current active workload.');
            DB::table('advisor_profiles')->updateOrInsert(['user_id' => $r->user()->id], collect($d)->except(['expertise', 'interests'])->all() + ['created_at' => now(), 'updated_at' => now()]);
            DB::table('advisor_expertise')->where('advisor_id', $r->user()->id)->delete();
            foreach ($d['expertise'] as $tag) {
                DB::table('advisor_expertise')->insert(['advisor_id' => $r->user()->id, 'tag' => $tag, 'created_at' => now(), 'updated_at' => now()]);
            }
            if (array_key_exists('interests', $d)) {
                DB::table('advisor_interests')->where('advisor_id', $r->user()->id)->delete();
                foreach ($d['interests'] as $tag) {
                    DB::table('advisor_interests')->insert(['advisor_id' => $r->user()->id, 'tag' => $tag, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });

        return $this->advisorProfile($r);
    }

    public function advisors(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_if($r->user()->role->value === 'recruiter', 403);
        $assignment = DB::table('advisor_assignments')->where('student_id', $r->user()->id)->where('status', 'active')->first();
        $query = DB::table('advisor_profiles')->join('users', 'users.id', '=', 'advisor_profiles.user_id')->where('users.status', 'active')->whereNull('users.deleted_at');
        if ($r->user()->role->value === 'student' && $assignment) {
            $query->where('users.id', $assignment->advisor_id);
        }
        $rows = $query->select('users.id', 'users.name', 'advisor_profiles.title', 'advisor_profiles.bio', 'advisor_profiles.capacity', 'advisor_profiles.is_accepting')->get();
        foreach ($rows as $a) {
            $a->places_left = max(0, $a->capacity - DB::table('advisor_assignments')->where('advisor_id', $a->id)->where('status', 'active')->count());
            $a->expertise = DB::table('advisor_expertise')->where('advisor_id', $a->id)->pluck('tag');
            $a->interests = DB::table('advisor_interests')->where('advisor_id', $a->id)->pluck('tag');
        }

        return ['data' => $rows, 'preferences' => DB::table('advisor_preferences')->where('student_id', $r->user()->id)->orderBy('rank')->get(), 'assignment' => $assignment];
    }

    public function preferences(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'student', 403);
        $d = $r->validated();
        DB::transaction(function () use ($r, $d) {
            User::whereKey($r->user()->id)->lockForUpdate()->first();
            abort_if(DB::table('advisor_assignments')->where('student_id', $r->user()->id)->where('status', 'active')->exists(), 422, 'You already have an advisor.');
            foreach ($d as $id) {
                abort_unless(User::whereKey($id)->where('role', 'advisor')->where('status', 'active')->exists(), 422, 'Invalid advisor.');
            }
            DB::table('advisor_preferences')->where('student_id', $r->user()->id)->delete();
            foreach (array_values($d) as $i => $id) {
                DB::table('advisor_preferences')->insert(['student_id' => $r->user()->id, 'advisor_id' => $id, 'rank' => $i + 1, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return ['message' => 'Preferences submitted for Admin approval.'];
    }

    public function allocations(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'admin', 403);

        $pending = DB::table('advisor_preferences')->where('status', 'pending');
        $students = (clone $pending)->select('student_id')->groupBy('student_id')->orderBy('student_id')->paginate($r->integer('per_page', 20));
        $rows = DB::table('advisor_preferences')->join('users as s', 's.id', '=', 'advisor_preferences.student_id')->join('users as a', 'a.id', '=', 'advisor_preferences.advisor_id')->where('advisor_preferences.status', 'pending')->whereIn('student_id', $students->pluck('student_id'))->select('advisor_preferences.*', 's.name as student_name', 'a.name as advisor_name')->orderBy('advisor_preferences.student_id')->orderBy('rank')->get();

        return ['data' => $rows, 'meta' => ['page' => $students->currentPage(), 'last_page' => $students->lastPage(), 'total' => $students->total()]];
    }

    public function allocate(Request $r, int $student)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'admin', 403);
        $d = $r->validated();
        (new AdvisorAllocation)->assign($student, $d['advisor_id'], $r->user()->id);

        return ['message' => 'Allocation confirmed.'];
    }

    public function reject(Request $r, int $student)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'admin', 403);
        $updated = DB::table('advisor_preferences')->where('student_id', $student)->where('status', 'pending')->update(['status' => 'rejected', 'updated_at' => now()]);
        abort_unless($updated > 0, 422, 'No pending advisor preferences to reject.');

        WorkflowActivity::notify([$student], 'Advisor request needs new preferences', 'Please choose another advisor preference.', 'general', '/advisors');
        WorkflowActivity::audit($r->user()->id, 'advisor.preference_rejected', 'student', $student, []);

        return ['message' => 'Request rejected. Student can choose again.'];
    }

    public function sessions(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $u = $r->user();
        abort_if($u->role->value === 'recruiter', 403);
        $q = DB::table('counselling_sessions')->join('users as s', 's.id', '=', 'counselling_sessions.student_id')->join('users as a', 'a.id', '=', 'counselling_sessions.advisor_id')->select('counselling_sessions.*', 's.name as student_name', 'a.name as advisor_name');
        if ($u->role->value !== 'admin') {
            $q->where($u->role->value === 'student' ? 'counselling_sessions.student_id' : 'counselling_sessions.advisor_id', $u->id);
        }
        $rows = $q->orderByDesc('scheduled_at')->paginate($r->integer('per_page', 20));
        foreach ($rows as $row) {
            $row->feedback = DB::table('session_feedback')->where('session_id', $row->id)->first();
        }

        return ['data' => $rows];
    }

    public function session(Request $r, ?int $id = null)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $u = $r->user();
        $d = $r->validated();

        return DB::transaction(function () use ($r, $u, $d, $id) {
            $old = $id ? DB::table('counselling_sessions')->where('id', $id)->lockForUpdate()->first() : null;
            abort_if($id && ! $old, 404);
            $student = $old?->student_id ?? $u->id;
            if ($old && $u->role->value === 'student') {
                abort_unless($old->student_id === $u->id, 403);
            } else {
                $this->access($r, $student);
            }
            abort_if($u->role->value === 'admin', 403);
            $advisor = $old?->advisor_id ?? DB::table('advisor_assignments')->where('student_id', $student)->where('status', 'active')->value('advisor_id');
            abort_unless($advisor, 422, 'An active advisor is required.');
            User::whereKey($advisor)->lockForUpdate()->firstOrFail();
            abort_unless(User::whereKey($advisor)->where('status', 'active')->exists(), 422, 'Advisor is inactive.');
            if ($old) {
                abort_unless(in_array($old->status, ['requested', 'confirmed']), 422, 'Session is already closed.');
                $action = $d['action'] ?? '';
                abort_unless($action === 'cancel' || $u->id === $advisor, 403);
                abort_if($action !== 'complete' && Carbon::parse($old->scheduled_at)->isPast(), 422, 'Session has already started.');
                abort_if($action === 'complete' && Carbon::parse($old->ends_at)->isFuture(), 422, 'Complete after the session ends.');
            } else {
                abort_unless($u->role->value === 'student', 403);
                $action = 'request';
            }
            $data = ['updated_at' => now()];
            if (in_array($action, ['request', 'reschedule'])) {
                abort_unless(isset($d['scheduled_at']), 422, 'Choose a future time.');
                $start = Carbon::parse($d['scheduled_at'])->utc();
                $end = $start->copy()->addMinutes($d['duration'] ?? 30);
                abort_if(DB::table('counselling_sessions')->where('advisor_id', $advisor)->whereIn('status', ['requested', 'confirmed'])->when($id, fn ($q) => $q->where('id', '!=', $id))->where('scheduled_at', '<', $end)->where('ends_at', '>', $start)->exists(), 422, 'Advisor has an overlapping session.');
                $data += ['scheduled_at' => $start, 'ends_at' => $end, 'status' => $action === 'request' ? 'requested' : 'confirmed'];
            }
            if ($action === 'request') {
                $data += ['student_id' => $student, 'advisor_id' => $advisor, 'type' => $d['type'], 'mode' => $d['mode'], 'location_or_link' => $d['location_or_link'] ?? null, 'student_note' => $d['student_note'] ?? null, 'created_at' => now()];
            }
            if ($action === 'confirm') {
                abort_unless($old->status === 'requested', 422, 'Only a requested session can be confirmed.');
                $data['status'] = 'confirmed';
            }if (in_array($action, ['cancel', 'decline'])) {
                $data += ['status' => $action === 'cancel' ? 'cancelled' : 'declined', 'cancelled_by' => $u->id, 'cancel_reason' => $d['cancel_reason']];
            }
            if ($action === 'complete') {
                abort_unless($old->status === 'confirmed', 422, 'Confirm the session before completing it.');
                $data['status'] = 'completed';
                DB::table('session_feedback')->updateOrInsert(['session_id' => $id], ['advisor_id' => $advisor, 'body' => $d['body'], 'next_step' => $d['next_step'], 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($id) {
                DB::table('counselling_sessions')->where('id', $id)->update($data);
            } else {
                $id = DB::table('counselling_sessions')->insertGetId($data);
            }

            WorkflowActivity::notify([$student, $advisor], 'Counselling session: '.($data['status'] ?? $action));
            WorkflowActivity::audit($u->id, 'session.'.$action, 'counselling_session', $id, ['status' => $data['status'] ?? null]);

            return ['data' => DB::table('counselling_sessions')->find($id)];
        });
    }

    public function plan(Request $r, int $student)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $student);
        $plan = DB::table('career_plans')->where('student_id', $student)->first();
        if ($plan) {
            $plan->milestones = DB::table('plan_milestones')->where('plan_id', $plan->id)->orderBy('sort_order')->get();
        }

        return ['data' => $plan];
    }

    public function savePlan(Request $r, int $student)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $student);
        abort_unless($r->user()->role->value === 'advisor', 403);
        $d = $r->validated();
        DB::transaction(function () use ($r, $student, $d) {
            User::whereKey($student)->lockForUpdate()->first();
            $this->access($r, $student);
            DB::table('career_plans')->updateOrInsert(['student_id' => $student], ['advisor_id' => $r->user()->id, 'target_role' => $d['target_role'], 'strengths' => $d['strengths'] ?? null, 'skill_gaps' => $d['skill_gaps'] ?? null, 'advisor_comment' => $d['advisor_comment'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('career_plans')->where('student_id', $student)->value('id');
            $retained = [];
            foreach ($d['milestones'] as $i => $m) {
                $existing = DB::table('plan_milestones')->where('plan_id', $id)->where('title', $m['title'])->first();
                if ($existing) {
                    DB::table('plan_milestones')->where('id', $existing->id)->update(['due_date' => $m['due_date'] ?? null, 'sort_order' => $i, 'updated_at' => now()]);
                    $retained[] = $existing->id;
                } else {
                    $retained[] = DB::table('plan_milestones')->insertGetId($m + ['plan_id' => $id, 'status' => 'not_started', 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            DB::table('plan_milestones')->where('plan_id', $id)->whereNotIn('id', $retained)->delete();
        });

        return $this->plan($r, $student);
    }

    public function milestone(Request $r, int $id)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $m = DB::table('plan_milestones')->find($id);
        abort_unless($m, 404);
        $p = DB::table('career_plans')->find($m->plan_id);
        $this->access($r, $p->student_id);
        $d = $r->validated();
        abort_unless($r->user()->role->value === 'advisor' || ($r->user()->role->value === 'student' && in_array($d['status'], ['not_started', 'in_progress'])), 403);
        DB::table('plan_milestones')->where('id', $id)->update($d + ['updated_at' => now()]);

        return ['message' => 'Progress saved.'];
    }
}
