<?php

namespace App\Policies;

use App\Models\ClubMembership;
use App\Models\User;

class ClubMembershipPolicy
{
    public function decide(User $user, ClubMembership $membership): bool
    {
        return $user->can('manageMembers', $membership->club);
    }
}
