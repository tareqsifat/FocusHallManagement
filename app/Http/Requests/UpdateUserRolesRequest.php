<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRolesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => ['distinct', Rule::in(array_map(fn (RoleName $r) => $r->value, RoleName::editable()))],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $target = $this->route('user');
            $chosen = $this->roles();

            if ($target->is($this->user()) && ! in_array(RoleName::SuperAdmin, $chosen, true)) {
                $validator->errors()->add('roles', 'You cannot remove your own Super Admin role.');
            }

            // Roles managed elsewhere (House Tutor, Club Admin) also count as "having a role".
            $kept = $target->loadMissing('roles')->roles->reject(fn ($role) => in_array($role->name, RoleName::editable(), true));
            if ($chosen === [] && $kept->isEmpty()) {
                $validator->errors()->add('roles', 'A user must keep at least one role.');
            }
        }];
    }

    /** @return list<RoleName> */
    public function roles(): array
    {
        return array_map(fn ($r) => RoleName::from($r), $this->validated('roles') ?? []);
    }

    public function messages(): array
    {
        return ['roles.*.in' => 'Only Super Admin, Provost and Hall Office Staff can be set here.'];
    }
}
