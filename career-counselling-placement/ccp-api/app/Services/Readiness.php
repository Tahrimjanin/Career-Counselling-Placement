<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class Readiness
{
    public function calculate(User $u): array
    {
        $cv = DB::table('cvs')->where('user_id', $u->id)->where('status', 'approved')->whereNull('deleted_at')->exists();
        $plan = DB::table('career_plans')->where('student_id', $u->id)->first();
        $tasks = $plan ? DB::table('plan_milestones')->where('plan_id', $plan->id)->pluck('status') : collect();
        $done = $tasks->filter(fn ($s) => in_array($s, ['completed', 'recommended'], true))->count();
        $advisor = DB::table('advisor_assignments')->where('student_id', $u->id)->where('status', 'active')->value('advisor_id');
        $recommendation = $advisor ? DB::table('cv_reviews')->join('cvs', 'cvs.id', '=', 'cv_reviews.cv_id')->where('cvs.user_id', $u->id)->where('cv_reviews.advisor_id', $advisor)->whereNotNull('placement_recommendation')->orderByDesc('cv_reviews.id')->value('placement_recommendation') : null;
        $ready = $cv && $tasks->count() > 0 && $done === $tasks->count() && $recommendation === 'ready';

        return ['state' => $ready ? 'Placement-ready' : (($cv || $tasks->count() || $recommendation) ? 'In progress' : 'Not started'), 'approved_cv' => $cv, 'milestones_completed' => $done, 'milestones_total' => $tasks->count(), 'advisor_recommends' => $recommendation === 'ready'];
    }
}
