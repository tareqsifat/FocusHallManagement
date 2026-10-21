<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\StudentProfile;
use App\Models\User;

class StudentProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isHallAdmin() || $user->hasRole(RoleName::HouseTutor);
    }

    /** Same rule as StudentProfile::scopeVisibleTo, so the list and the profile page never disagree. */
    public function view(User $user, StudentProfile $student): bool
    {
        return StudentProfile::visibleTo($user)->whereKey($student->id)->exists();
    }

    public function allocateSeat(User $user, StudentProfile $student): bool
    {
        return $user->isHallAdmin();
    }
}
