<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PlatformReports
{
    public function build(?string $from = null, ?string $to = null): array
    {
        $apps = DB::table('applications')->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
        $students = (clone $apps)->distinct()->count('user_id');
        $placed = DB::table('placement_records')->whereIn('application_id', (clone $apps)->select('id'))->distinct()->count('student_id');
        $funnel = (clone $apps)->selectRaw('workflow_status as status, count(*) as total')->groupBy('workflow_status')->orderBy('workflow_status')->get();
        $workload = DB::table('users')->where('role', 'advisor')->whereNull('deleted_at')->select('id', 'name')->get()->map(function ($a) {
            $a->total = DB::table('advisor_assignments')->where('advisor_id', $a->id)->where('status', 'active')->count();

            return $a;
        });
        $skills = DB::table('student_skill')->join('skills', 'skills.id', '=', 'student_skill.skill_id')->whereIn('student_skill.user_id', (clone $apps)->select('user_id'))->selectRaw('skills.name, count(*) as total')->groupBy('skills.id', 'skills.name')->orderByDesc('total')->limit(10)->get();

        return ['from' => $from, 'to' => $to, 'applicants' => $students, 'placed_students' => $placed, 'placement_rate' => $students ? round(100 * $placed / $students, 2) : 0, 'applications' => (clone $apps)->count(), 'funnel' => $funnel, 'advisor_workload' => $workload, 'top_skills' => $skills, 'definition' => 'Dates select the application cohort. Placement rate = unique placed students in that cohort / unique applicants. Advisor workload is current, independent of date filters.'];
    }
}
