<?php

namespace App\Policies;

use App\Enums\NoticeCategory;
use App\Enums\RoleName;
use App\Models\Notice;
use App\Models\User;

class NoticePolicy
{
    /** Hall admins publish any category; club admins publish Club notices only. */
    public function viewAny(User $user): bool
    {
        return $this->publishes($user);
    }

    public function create(User $user): bool
    {
        return $this->publishes($user);
    }

    /** Public pages show published notices only, even to the people who can edit a draft (BR-11). */
    public function view(?User $user, Notice $notice): bool
    {
        return $notice->isPublished();
    }

    public function update(User $user, Notice $notice): bool
    {
        return $user->isHallAdmin()
            || ($user->hasRole(RoleName::ClubAdmin) && $notice->category === NoticeCategory::Club && $notice->created_by === $user->id);
    }

    public function delete(User $user, Notice $notice): bool
    {
        return $this->update($user, $notice);
    }

    /** @return list<NoticeCategory> */
    public static function categoriesFor(User $user): array
    {
        return $user->isHallAdmin() ? NoticeCategory::cases() : [NoticeCategory::Club];
    }

    private function publishes(User $user): bool
    {
        return $user->isHallAdmin() || $user->hasRole(RoleName::ClubAdmin);
    }
}
