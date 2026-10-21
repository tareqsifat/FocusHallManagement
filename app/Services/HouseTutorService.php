<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\HouseTutor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** A house tutor is a user account, a tutor profile and block assignments, written together. */
class HouseTutorService
{
    public function create(array $data): HouseTutor
    {
        return DB::transaction(function () use ($data) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->status = UserStatus::Active;
            $user->email_verified_at = now();
            $user->save();
            $user->assignRole(RoleName::HouseTutor);

            $tutor = $user->houseTutor()->create($data);
            $tutor->blocks()->sync($data['block_ids']);

            return $tutor;
        });
    }

    public function update(HouseTutor $tutor, array $data): HouseTutor
    {
        return DB::transaction(function () use ($tutor, $data) {
            $tutor->user->fill(['name' => $data['name'], 'email' => $data['email']]);
            if (filled($data['password'] ?? null)) {
                $tutor->user->password = $data['password'];
            }
            $tutor->user->save();

            $tutor->update($data);
            $tutor->blocks()->sync($data['block_ids']);

            return $tutor;
        });
    }

    /**
     * Deactivated tutors cannot log in, be assigned complaints or see block students; their history stays.
     * The tutor flag and the account status change together so they never disagree.
     */
    public function setActive(HouseTutor $tutor, bool $active): void
    {
        DB::transaction(function () use ($tutor, $active) {
            $tutor->is_active = $active;
            $tutor->save();

            $tutor->user->status = $active ? UserStatus::Active : UserStatus::Inactive;
            $tutor->user->save();
        });
    }
}
