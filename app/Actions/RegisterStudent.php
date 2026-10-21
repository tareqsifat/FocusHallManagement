<?php

namespace App\Actions;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterStudent
{
    /**
     * Creates a pending student account. It cannot reach protected pages until staff approve it.
     *
     * @param  array{name: string, email: string, password: string, registration_no: string, department: string, session: string, phone: string}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->status = UserStatus::Pending;
            $user->save();

            $user->studentProfile()->create([
                'registration_no' => $data['registration_no'],
                'department' => $data['department'],
                'session' => $data['session'],
                'phone' => $data['phone'],
            ]);
            $user->assignRole(RoleName::Student);

            return $user;
        });
    }
}
