<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClubService
{
    public function save(Club $club, array $data, ?UploadedFile $logo): Club
    {
        $club->fill($data);

        $old = $club->logo_path;
        if ($logo) {
            // Generated name on the private disk; served by ClubController::logo (BR-13).
            $club->logo_path = $logo->store('club-logos', 'local');
        }
        $club->save();

        if ($logo && $old) {
            Storage::disk('local')->delete($old);
        }

        return $club;
    }

    /**
     * Super Admin assigns club admins. The club_admin role follows the pivot: users gain it
     * when they become an admin and lose it when removed (a student leads at most one club).
     *
     * @param  list<int>  $userIds
     */
    public function syncAdmins(Club $club, array $userIds): void
    {
        DB::transaction(function () use ($club, $userIds) {
            $changes = $club->admins()->sync($userIds);

            User::whereKey($changes['attached'])->get()->each->assignRole(RoleName::ClubAdmin);
            User::whereKey($changes['detached'])->get()->each->removeRole(RoleName::ClubAdmin);
        });
    }
}
