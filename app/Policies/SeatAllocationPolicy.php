<?php

namespace App\Policies;

use App\Models\SeatAllocation;
use App\Models\User;

class SeatAllocationPolicy
{
    public function vacate(User $user, SeatAllocation $allocation): bool
    {
        return $user->isHallAdmin();
    }
}
