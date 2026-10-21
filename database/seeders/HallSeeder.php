<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Block;
use App\Models\User;
use App\Services\HouseTutorService;
use App\Services\RoomService;
use Illuminate\Database\Seeder;

/** Blocks, rooms and seats; staff demo accounts; five house tutors. */
class HallSeeder extends Seeder
{
    public function run(RoomService $rooms, HouseTutorService $tutors): void
    {
        DatabaseSeeder::travelTo(400);

        // 3 blocks, 32 rooms, capacities 2–4 (96 seats).
        $layout = ['North' => ['N', 3, 4], 'South' => ['S', 3, 4], 'Extension' => ['E', 2, 4]];
        $capacities = [4, 3, 2, 4, 3, 4];
        $i = 0;
        foreach ($layout as $name => [$code, $floors, $perFloor]) {
            $block = Block::create(['name' => $name, 'code' => $code]);
            for ($floor = 1; $floor <= $floors; $floor++) {
                for ($n = 1; $n <= $perFloor; $n++) {
                    $rooms->create([
                        'block_id' => $block->id,
                        'room_no' => $code.$floor.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
                        'floor' => $floor,
                        'capacity' => $capacities[$i++ % count($capacities)],
                    ]);
                }
            }
        }

        $this->staff('System Administrator', 'admin@kjh.test', RoleName::SuperAdmin);
        $this->staff('Prof. Dr. Kamrul Hasan', 'provost@kjh.test', RoleName::Provost);
        $this->staff('Abdul Mannan', 'office@kjh.test', RoleName::OfficeStaff);
        $this->staff('Shahana Parvin', 'office2@kjh.test', RoleName::OfficeStaff);

        $blocks = Block::pluck('id', 'name');
        $tutorList = [
            // The demo tutor covers North only.
            ['Dr. Nusrat Jahan', 'tutor@kjh.test', 'Assistant Professor', 'Chemistry', ['North']],
            ['Dr. Mizanur Rahman', 'tutor2@kjh.test', 'Associate Professor', 'Physics', ['North', 'South']],
            ['Farhana Kabir', 'tutor3@kjh.test', 'Lecturer', 'English', ['South']],
            ['Dr. Selim Reza', 'tutor4@kjh.test', 'Assistant Professor', 'Mathematics', ['Extension']],
            ['ড. আনোয়ার হোসেন', 'tutor5@kjh.test', 'Professor', 'Bangla', ['Extension', 'South']],
        ];
        foreach ($tutorList as $k => [$name, $email, $designation, $department, $tutorBlocks]) {
            $tutors->create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'designation' => $designation,
                'department' => $department,
                'phone' => '0181100000'.$k,
                'block_ids' => array_map(fn ($b) => $blocks[$b], $tutorBlocks),
            ]);
        }
    }

    private function staff(string $name, string $email, RoleName $role): void
    {
        $user = new User(['name' => $name, 'email' => $email, 'password' => 'password']);
        $user->status = UserStatus::Active;
        $user->email_verified_at = now();
        $user->save();
        $user->assignRole($role);
    }
}
