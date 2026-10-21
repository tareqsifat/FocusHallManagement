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

    public function decideRegistration(User $actor, User $applicant): bool
    {
        return $actor->isHallAdmin()
            && $applicant->status === UserStatus::Pending
            && $applicant->hasRole(RoleName::Student);
    }
}
