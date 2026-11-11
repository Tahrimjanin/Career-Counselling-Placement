<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class WorkspacePolicy
{
    public function student(User $u, int $id): bool
    {
        return $u->role->value === 'admin' || ($u->role->value === 'student' && $u->id === $id) || ($u->role->value === 'advisor' && DB::table('advisor_assignments')->where('student_id', $id)->where('advisor_id', $u->id)->where('status', 'active')->exists());
    }

    public function candidate(User $u, object $a): bool
    {
        return $u->role->value === 'admin' || ($u->role->value === 'student' && $u->id === $a->user_id) || ($u->role->value === 'recruiter' && $u->company_id && $a->forwarded_at && DB::table('opportunities')->where('id', $a->opportunity_id)->where('company_id', $u->company_id)->exists());
    }
}
