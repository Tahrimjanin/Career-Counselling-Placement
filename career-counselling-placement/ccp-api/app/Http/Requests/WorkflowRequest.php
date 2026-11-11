<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class WorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('workflow', [class_basename($this->route()->getControllerClass()), $this->route()->getActionMethod()]);
    }

    public function rules(): array
    {
        $key = class_basename($this->route()->getControllerClass()).'@'.$this->route()->getActionMethod();
        $rules = match ($key) {
            'AdvisingController@milestone' => ['status' => 'required|in:not_started,in_progress,completed,recommended'],
            'AdvisingController@savePlan' => ['target_role' => 'required|string|max:255', 'strengths' => 'nullable|string|max:5000', 'skill_gaps' => 'nullable|string|max:5000', 'advisor_comment' => 'nullable|string|max:5000', 'milestones' => 'required|array|min:1|max:30', 'milestones.*.title' => 'required|string|max:255|distinct', 'milestones.*.due_date' => 'nullable|date'],
            'AdvisingController@session' => ['action' => 'nullable|in:confirm,reschedule,decline,cancel,complete', 'type' => 'required_without:action|in:Career planning,CV review,Mock interview,Skill gap', 'scheduled_at' => 'required_without:action|date|after:now', 'duration' => 'nullable|integer|min:15|max:120', 'mode' => 'required_without:action|in:Online,In person', 'location_or_link' => 'nullable|string|max:255', 'student_note' => 'nullable|string|max:2000', 'body' => 'required_if:action,complete|string|max:5000', 'next_step' => 'required_if:action,complete|string|max:2000', 'cancel_reason' => 'required_if:action,cancel,decline|string|max:1000'],
            'AdvisingController@allocate' => ['advisor_id' => 'required|integer'],
            'AdvisingController@preferences' => ['first' => 'required|integer|different:second', 'second' => 'required|integer'],
            'AdvisingController@saveAdvisorProfile' => ['title' => 'required|string|max:255', 'bio' => 'required|string|max:3000', 'capacity' => 'required|integer|min:1|max:200', 'is_accepting' => 'required|boolean', 'expertise' => 'required|array|min:1|max:20', 'expertise.*' => 'required|string|max:100|distinct:ignore_case', 'interests' => 'sometimes|array|max:20', 'interests.*' => 'required|string|max:100|distinct:ignore_case'],
            'CvLifecycleController@review' => ['decision' => 'required|in:approved,changes_requested', 'strengths' => 'required|string|max:3000', 'improvements' => 'required|string|max:3000', 'placement_recommendation' => 'required|in:ready,not_ready'],
            'CvLifecycleController@create' => ['title' => 'required|string|max:150', 'source' => 'required|in:builder,uploaded', 'content' => 'required_if:source,builder|array:name,email,summary,education,skills,experience,title', 'content.title' => 'nullable|string|max:150', 'content.name' => 'required_if:source,builder|string|max:150', 'content.email' => 'required_if:source,builder|email|max:255', 'content.summary' => 'required_if:source,builder|string|max:3000', 'content.education' => 'required_if:source,builder|string|max:5000', 'content.skills' => 'required_if:source,builder|string|max:3000', 'content.experience' => 'nullable|string|max:5000', 'file' => 'required_if:source,uploaded|file|mimes:pdf,docx|max:5120'],
            'OpportunityLifecycleController@save' => ['saved' => 'required|boolean'],
            'OpportunityLifecycleController@review' => ['decision' => 'required|in:approved,rejected', 'reason' => 'required_if:decision,rejected|nullable|string|max:3000'],
            'OpportunityLifecycleController@create' => ['title' => 'required|string|max:255', 'type' => 'required|in:Internship,Job,Training,Part-time', 'location' => 'required|string|max:255', 'work_mode' => 'required|in:On-site,Hybrid,Remote', 'is_paid' => 'required|boolean', 'stipend_min' => 'nullable|integer|min:0', 'stipend_max' => 'nullable|integer|gte:stipend_min', 'stipend_label' => 'nullable|string|max:255', 'description' => 'required|string|max:10000', 'responsibilities' => 'required|string|max:10000', 'requirements' => 'required|string|max:10000', 'skills' => 'required|array|max:30', 'skills.*' => 'required|string|max:100', 'min_cgpa' => 'nullable|numeric|min:0|max:4', 'deadline' => 'required|date|after_or_equal:'.now('Asia/Dhaka')->toDateString()],
            'PlacementController@respond' => ['decision' => 'required|in:accepted,declined'],
            'PlacementController@offer' => ['details' => 'required|string|max:5000', 'expires_at' => 'required|date|after:now', 'start_date' => 'required|date|after_or_equal:today'],
            'PlacementController@feedback' => ['action' => 'nullable|in:cancel', 'rating' => 'required_without:action|integer|min:1|max:5', 'recommendation' => 'required_without:action|in:hire,hold,reject', 'comments' => 'required|string|max:3000'],
            'PlacementController@interview' => ['scheduled_at' => 'required|date|after:now', 'duration_min' => 'required|integer|min:15|max:240', 'type' => 'required|string|max:100', 'mode' => 'required|in:Online,In person', 'interviewer' => 'required|string|max:255', 'link_or_location' => 'required|string|max:255', 'prep_notes' => 'nullable|string|max:3000'],
            'PlacementController@change' => ['status' => 'required|string', 'reason' => 'nullable|string|max:3000', 'cover_note' => 'nullable|string|max:3000'],
            'PlacementController@apply' => ['opportunity_id' => 'required|integer|exists:opportunities,id', 'cv_id' => 'required|integer|exists:cvs,id', 'cover_note' => 'nullable|string|max:3000'],

            default => []
        };
        if ($key === 'AdvisingController@session') {
            $rules['action'] = $this->route('id') ? 'required|in:confirm,reschedule,decline,cancel,complete' : 'prohibited';
        }
        if ($this->isMethod('GET')) {
            $rules += ['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:50', 'q' => 'nullable|string|max:200', 'status' => 'nullable|string|max:60', 'review' => 'nullable|in:pending,corrections,forwarded', 'sort' => 'nullable|in:newest,deadline,match', 'type' => 'nullable|string|max:50', 'work_mode' => 'nullable|in:On-site,Hybrid,Remote', 'location' => 'nullable|string|max:255', 'skill' => 'nullable|string|max:100', 'paid' => 'nullable|boolean', 'saved' => 'nullable|boolean', 'cv_id' => 'nullable|integer', 'offers' => 'nullable|boolean'];
        }

        return $rules;
    }
}
