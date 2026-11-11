<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdvisorAllocation
{
    public function assign(int $student, int $advisor, int $actor): void
    {
        DB::transaction(function () use ($student, $advisor, $actor) {
            $u = User::whereKey($student)->lockForUpdate()->firstOrFail();
            $a = User::whereKey($advisor)->lockForUpdate()->firstOrFail();
            abort_unless($u->role->value === 'student' && $a->role->value === 'advisor' && $a->status === 'active', 422, 'Choose an active advisor and a student.');
            $p = DB::table('advisor_profiles')->where('user_id', $advisor)->first();
            abort_unless($p && $p->is_accepting, 422, 'Advisor is not accepting students.');
            $existing = DB::table('advisor_assignments')->where('student_id', $student)->where('status', 'active')->first();
            if ($existing && $existing->advisor_id === $advisor) {
                return;
            }
            abort_if(DB::table('advisor_assignments')->where('advisor_id', $advisor)->where('status', 'active')->count() >= $p->capacity, 422, 'Advisor is at capacity.');
            DB::table('advisor_assignments')->where('student_id', $student)->where('status', 'active')->update(['status' => 'ended', 'ended_at' => now(), 'updated_at' => now()]);
            DB::table('advisor_assignments')->insert(['student_id' => $student, 'advisor_id' => $advisor, 'assigned_by' => $actor, 'status' => 'active', 'started_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $u->update(['advisor_id' => $advisor]);
            DB::table('advisor_preferences')->where('student_id', $student)->update(['status' => 'allocated', 'updated_at' => now()]);
            WorkflowActivity::notify([$student, $advisor], 'Advisor allocation confirmed');
            WorkflowActivity::audit($actor, 'advisor.allocated', 'student', $student, ['advisor_id' => $advisor]);
        });
    }
}
