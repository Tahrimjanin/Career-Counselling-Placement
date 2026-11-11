<?php

namespace App\Policies;

use App\Models\User;

class WorkflowPolicy
{
    public const ROLES = [
        'AdvisingController' => ['student' => ['student', 'advisor', 'admin'], 'advisorProfile' => ['advisor'], 'saveAdvisorProfile' => ['advisor'], 'advisors' => ['student', 'advisor', 'admin'], 'preferences' => ['student'], 'allocations' => ['admin'], 'allocate' => ['admin'], 'reject' => ['admin'], 'sessions' => ['student', 'advisor', 'admin'], 'session' => ['student', 'advisor'], 'plan' => ['student', 'advisor', 'admin'], 'savePlan' => ['advisor'], 'milestone' => ['student', 'advisor']],
        'CvLifecycleController' => ['index' => ['student', 'advisor'], 'create' => ['student'], 'submit' => ['student'], 'review' => ['advisor'], 'show' => ['student', 'advisor', 'admin', 'recruiter'], 'download' => ['student', 'advisor', 'admin', 'recruiter']],
        'OpportunityLifecycleController' => ['index' => ['student', 'advisor', 'admin', 'recruiter'], 'create' => ['recruiter'], 'review' => ['admin'], 'save' => ['student']],
        'PlacementController' => ['index' => ['student', 'admin', 'recruiter'], 'detail' => ['student', 'admin', 'recruiter'], 'eligibility' => ['student'], 'apply' => ['student'], 'change' => ['student', 'admin', 'recruiter'], 'interviews' => ['student', 'admin', 'recruiter'], 'interview' => ['recruiter'], 'feedback' => ['recruiter'], 'offer' => ['recruiter'], 'respond' => ['student'], 'placements' => ['admin']],
    ];

    public function perform(User $u, string $controller, string $action): bool
    {
        return $u->status === 'active' && in_array($u->role->value, self::ROLES[$controller][$action] ?? [], true) && ! ($action === 'apply' && $u->profile?->verification_status !== 'verified');
    }
}
