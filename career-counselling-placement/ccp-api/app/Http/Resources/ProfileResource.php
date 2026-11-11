<?php

namespace App\Http\Resources;

use App\Enums\Role;
use App\Services\ProfileCompletion;
use App\Services\Readiness;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        $student = $this->role === Role::Student;
        $profile = $this->profile?->only(['student_no', 'department', 'batch', 'cgpa', 'ssc_institution', 'ssc_board', 'ssc_year', 'ssc_gpa', 'hsc_institution', 'hsc_board', 'hsc_year', 'hsc_gpa', 'career_objective', 'interests', 'has_experience', 'has_certificates', 'wizard_step', 'verification_status', 'verification_reason', 'verified_at']) ?? [];
        $data = ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone, 'role' => $this->role->value, 'profile' => $profile];
        if ($this->role === Role::Recruiter) {
            $data['company'] = DB::table('companies')->where('id', $this->company_id)->first(['name', 'industry', 'website', 'description']);
        }
        if (! $student) {
            return $data;
        }
        foreach (['technical', 'soft'] as $type) {
            $data[$type.'_skills'] = DB::table('student_skill')->join('skills', 'skills.id', '=', 'student_skill.skill_id')->where('user_id', $this->id)->where('type', $type)->pluck('name');
        }
        foreach (['experiences' => 'profile_experiences', 'certificates' => 'profile_certificates'] as $key => $table) {
            $data[$key] = DB::table($table)->where('user_id', $this->id)->get(['title', 'org', 'start_date', 'end_date', 'description']);
        }
        $data['completion'] = app(ProfileCompletion::class)->calculate($this->resource);
        $data['readiness'] = app(Readiness::class)->calculate($this->resource);

        return $data;
    }
}
