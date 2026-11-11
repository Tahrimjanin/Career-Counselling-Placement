<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfileCompletion
{
    public function calculate(User $u): array
    {
        $p = $u->profile;
        $fields = ['name' => $u->name, 'email' => $u->email, 'phone' => $u->phone];
        foreach (['student_no', 'department', 'batch', 'cgpa', 'ssc_institution', 'ssc_board', 'ssc_year', 'ssc_gpa', 'hsc_institution', 'hsc_board', 'hsc_year', 'hsc_gpa', 'career_objective'] as $key) {
            $fields[$key] = $p?->$key;
        }
        $fields['interests'] = count($p?->interests ?? []) > 0;
        foreach (['technical', 'soft'] as $type) {
            $fields[$type.'_skills'] = DB::table('student_skill')->join('skills', 'skills.id', '=', 'student_skill.skill_id')->where('user_id', $u->id)->where('type', $type)->exists();
        }
        $fields['experience'] = $p && ($p->has_experience === false || ($p->has_experience === true && DB::table('profile_experiences')->where('user_id', $u->id)->exists()));
        $fields['certificates'] = $p && ($p->has_certificates === false || ($p->has_certificates === true && DB::table('profile_certificates')->where('user_id', $u->id)->exists()));
        $missing = array_keys(array_filter($fields, fn ($v) => $v === null || $v === false || $v === '' || (is_string($v) && trim($v) === '')));

        return ['percent' => (int) floor((count($fields) - count($missing)) * 100 / count($fields)), 'missing' => $missing, 'required_count' => count($fields)];
    }
}
