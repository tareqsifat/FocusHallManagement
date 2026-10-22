<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRoleService
{
    /**
     * Sets the user's editable roles to exactly $roles. Roles that belong to another
     * flow (Student, House Tutor, Club Admin) are left untouched.
     *
     * @param  list<RoleName>  $roles
     */
    public function update(User $user, array $roles): void
    {
        $editable = Role::whereIn('name', RoleName::editable())->pluck('id', 'name');
        $wanted = $editable->only(array_map(fn (RoleName $r) => $r->value, $roles));

        DB::transaction(function () use ($user, $editable, $wanted) {
            $user->roles()->detach($editable->diff($wanted)->values());
            $user->roles()->syncWithoutDetaching($wanted->values());
        });

        $user->unsetRelation('roles');
    }
}
