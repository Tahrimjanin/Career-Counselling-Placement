<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = ['user_id', 'student_no', 'department', 'batch', 'cgpa', 'ssc_institution', 'ssc_board', 'ssc_year', 'ssc_gpa', 'hsc_institution', 'hsc_board', 'hsc_year', 'hsc_gpa', 'career_objective', 'interests', 'has_experience', 'has_certificates', 'completion_percent', 'wizard_step', 'verification_status', 'verified_by', 'verified_at', 'verification_reason'];

    protected function casts(): array
    {
        return ['interests' => 'array', 'has_experience' => 'boolean', 'has_certificates' => 'boolean', 'verified_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
