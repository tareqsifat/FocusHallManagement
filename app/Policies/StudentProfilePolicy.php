<?php

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\User;

class StudentProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isHallAdmin();
    }

    public function view(User $user, StudentProfile $student): bool
    {
        return $user->isHallAdmin() || $student->user_id === $user->id;
    }

    public function allocateSeat(User $user, StudentProfile $student): bool
    {
        return $user->isHallAdmin();
    }
}
