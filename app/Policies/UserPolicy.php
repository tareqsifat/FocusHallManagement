<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    public function reviewRegistrations(User $actor): bool
    {
        return $actor->isHallAdmin();
    }

    /** "Manage users and roles" is Super Admin only (Table 4). */
    public function manageRoles(User $actor): bool
    {
        return $actor->hasRole(RoleName::SuperAdmin);
    }

    /** Student accounts cannot be given staff roles. */
    public function updateRoles(User $actor, User $target): bool
    {
        return $this->manageRoles($actor) && ! $target->hasRole(RoleName::Student);
    }

    public function decideRegistration(User $actor, User $applicant): bool
    {
        return $actor->isHallAdmin()
            && $applicant->status === UserStatus::Pending
            && $applicant->hasRole(RoleName::Student);
    }
}
