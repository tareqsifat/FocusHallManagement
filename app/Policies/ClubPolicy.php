<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Club;
use App\Models\User;

class ClubPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::SuperAdmin);
    }

    /** Edit the club page: Super Admin, or an admin of this club (BR-12). */
    public function update(User $user, Club $club): bool
    {
        return $user->hasRole(RoleName::SuperAdmin) || $club->isAdministeredBy($user);
    }

    /** See and decide membership requests. */
    public function manageMembers(User $user, Club $club): bool
    {
        return $this->update($user, $club);
    }

    public function assignAdmins(User $user, Club $club): bool
    {
        return $user->hasRole(RoleName::SuperAdmin);
    }

    /** BR-03: approved students only (the active middleware has already checked approval). */
    public function join(User $user, Club $club): bool
    {
        return $user->hasRole(RoleName::Student) && $user->isActive() && $user->studentProfile !== null;
    }
}
