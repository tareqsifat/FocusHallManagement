<?php

namespace Database\Seeders;

use App\Enums\ResidencyStatus;
use App\Enums\UserStatus;
use App\Models\Club;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ClubMembershipService;
use App\Services\ClubService;
use Illuminate\Database\Seeder;

/** Four clubs with presidents, active members and pending requests. */
class ClubSeeder extends Seeder
{
    public function run(ClubService $clubs, ClubMembershipService $memberships): void
    {
        DatabaseSeeder::travelTo(200);

        $students = StudentProfile::query()
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->where('residency_status', ResidencyStatus::Resident)
            ->with('user')
            ->orderBy('id')
            ->get();
        $clubAdmin = User::where('email', 'clubadmin@student.du.test')->firstOrFail();
        $demoStudent = User::where('email', 'student@student.du.test')->firstOrFail();
        $pool = $students->reject(fn ($p) => in_array($p->user_id, [$clubAdmin->id, $demoStudent->id], true))->values();

        $definitions = [
            ['Debating Club', 'Weekly parliamentary debates and the annual inter-hall championship.', [$clubAdmin->id]],
            ['Cultural Club', 'Music, recitation and drama. Organises Pohela Boishakh and the hall cultural night.', [$pool[0]->user_id, $pool[1]->user_id]],
            ['Sports Club', 'Football, cricket and carrom tournaments for hall residents.', [$pool[2]->user_id]],
            ['Science Club', 'Project fairs, olympiad preparation and a monthly science talk.', [$pool[3]->user_id]],
        ];

        foreach ($definitions as $c => [$name, $description, $adminIds]) {
            $club = $clubs->create(['name' => $name, 'description' => $description], null, $adminIds);
            $decider = User::find($adminIds[0]);

            // 4–6 active members (admins join their own club too), then 2 pending requests.
            $members = $pool->slice(4 + $c * 6, 6 - $c % 3)->pluck('id')->merge(
                StudentProfile::whereIn('user_id', $adminIds)->pluck('id')
            );
            foreach ($members as $k => $profileId) {
                DatabaseSeeder::travelTo(150 - $k * 7);
                $membership = $memberships->request($club, StudentProfile::find($profileId));
                DatabaseSeeder::travelTo(148 - $k * 7);
                $memberships->approve($membership, $decider);
            }

            DatabaseSeeder::travelTo(4 - $c % 2);
            foreach ($pool->slice(30 + $c * 2, 2) as $profile) {
                $memberships->request($club, $profile);
            }
        }

        // The demo student: a member of Debating, a pending request at Science, rejected once by Sports.
        $profile = $demoStudent->studentProfile;
        DatabaseSeeder::travelTo(90);
        $memberships->approve($memberships->request(Club::where('name', 'Debating Club')->first(), $profile), $clubAdmin);
        $sports = Club::where('name', 'Sports Club')->first();
        $memberships->reject($memberships->request($sports, $profile), $sports->admins()->first());
        DatabaseSeeder::travelTo(2);
        $memberships->request(Club::where('name', 'Science Club')->first(), $profile);
    }
}
