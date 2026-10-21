<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClubAdminsRequest extends FormRequest
{
    public function rules(): array
    {
        // Club admins are students who lead the club.
        $eligible = User::where('status', UserStatus::Active)
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::Student))
            ->pluck('id');

        return [
            'admin_ids' => ['array'],
            'admin_ids.*' => ['integer', 'distinct', Rule::in($eligible)],
        ];
    }

    public function messages(): array
    {
        return ['admin_ids.*.in' => 'Club admins must be approved students.'];
    }
}
