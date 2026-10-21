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
        return self::adminRules($this->route('club')->id);
    }

    public function messages(): array
    {
        return self::adminMessages();
    }

    /**
     * Shared with ClubRequest, which accepts admins when a club is created.
     * Club admins (the president and at most one other) must be approved students
     * who do not already lead another club.
     */
    public static function adminRules(?int $clubId): array
    {
        $eligible = User::where('status', UserStatus::Active)
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::Student))
            ->pluck('id');

        return [
            'admin_ids' => ['array', 'max:'.config('hall.club_max_admins')],
            'admin_ids.*' => [
                'integer', 'distinct', Rule::in($eligible),
                Rule::unique('club_admins', 'user_id')->when($clubId, fn ($rule) => $rule->whereNot('club_id', $clubId)),
            ],
        ];
    }

    public static function adminMessages(): array
    {
        return [
            'admin_ids.*.in' => 'Club admins must be approved students.',
            'admin_ids.max' => 'A club can have at most :max admins.',
            'admin_ids.*.unique' => 'This student is already an admin of another club.',
        ];
    }
}
