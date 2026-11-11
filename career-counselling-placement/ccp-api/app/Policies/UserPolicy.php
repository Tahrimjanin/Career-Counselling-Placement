<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function create(User $actor): bool
    {
        return $actor->role === Role::Admin;
    }

    public function viewVerification(User $actor, User $student): bool
    {
        return $actor->role === Role::Admin && $student->role === Role::Student;
    }

    public function verify(User $actor, User $student): bool
    {
        return $this->viewVerification($actor, $student);
    }

    public function viewAnyVerification(User $actor): bool
    {
        return $actor->role === Role::Admin;
    }

    public function updateProfile(User $actor, User $owner): bool
    {
        return $actor->id === $owner->id;
    }
}
