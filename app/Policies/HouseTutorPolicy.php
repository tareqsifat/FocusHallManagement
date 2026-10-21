<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\HouseTutor;
use App\Models\User;

class HouseTutorPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manages($user);
    }

    public function create(User $user): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, HouseTutor $tutor): bool
    {
        return $this->manages($user);
    }

    private function manages(User $user): bool
    {
        return $user->hasRole(RoleName::Provost, RoleName::SuperAdmin);
    }
}
