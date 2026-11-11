<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateProfile', $this->user());
    }

    public function rules(): array
    {
        $step = (int) $this->route('step');
        if ($this->user()->role !== Role::Student) {
            abort_unless($step === 1, 403);
        }
        $short = 'nullable|string|max:120';
        $rules = match ($step) {
            1 => ['name' => 'sometimes|required|string|max:120', 'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9 ()-]{5,30}$/']],
            2 => ['student_no' => ['nullable', 'string', 'max:60', Rule::unique('student_profiles', 'student_no')->ignore($this->user()->id, 'user_id')], 'department' => $short, 'batch' => $short, 'cgpa' => 'nullable|numeric|between:0,4', 'ssc_institution' => $short, 'ssc_board' => $short, 'ssc_year' => 'nullable|integer|between:1950,'.date('Y'), 'ssc_gpa' => 'nullable|numeric|between:0,5', 'hsc_institution' => $short, 'hsc_board' => $short, 'hsc_year' => 'nullable|integer|between:1950,'.date('Y'), 'hsc_gpa' => 'nullable|numeric|between:0,5'],
            3,4 => ['skills' => 'sometimes|array|max:30', 'skills.*' => 'required|string|max:80|distinct:ignore_case'],
            5,6 => [$step === 5 ? 'has_experience' : 'has_certificates' => 'sometimes|nullable|boolean', 'entries' => 'sometimes|array|max:30', 'entries.*' => 'array:title,org,start_date,end_date,description', 'entries.*.title' => 'required|string|max:150', 'entries.*.org' => 'required|string|max:150', 'entries.*.start_date' => 'nullable|date|before_or_equal:today', 'entries.*.end_date' => 'nullable|date|after_or_equal:entries.*.start_date', 'entries.*.description' => 'nullable|string|max:2000'],
            7 => ['career_objective' => 'nullable|string|max:3000', 'interests' => 'sometimes|array|max:20', 'interests.*' => 'required|string|max:100|distinct:ignore_case'],
            8 => [], default => abort(404),
        };

        if ($step === 1 && $this->user()->role === Role::Recruiter) {
            $rules += ['company' => 'sometimes|array:name,industry,website,description', 'company.name' => 'sometimes|required|string|max:150', 'company.industry' => 'nullable|string|max:120', 'company.website' => 'nullable|url:http,https|max:255', 'company.description' => 'nullable|string|max:3000'];
        }

        return $rules + ['advance' => 'sometimes|boolean'];
    }
}
