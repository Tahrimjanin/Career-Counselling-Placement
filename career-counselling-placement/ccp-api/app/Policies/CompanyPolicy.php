<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function updateProfile(User $actor, Company $company): bool
    {
        return $actor->role === Role::Recruiter && $actor->company_id === $company->id;
    }
}
