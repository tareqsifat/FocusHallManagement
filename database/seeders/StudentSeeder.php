<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Seat;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\SeatAllocationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 61 students: 40 residents (incl. both student demo accounts), 15 attached (3 of them former
 * residents, so allocation history exists), 5 pending and 1 rejected registration.
 */
class StudentSeeder extends Seeder
{
    private const FIRST = ['Tanvir', 'Rakib', 'Sohel', 'Mahmud', 'Nayeem', 'Fahim', 'Imran', 'Shakil', 'Rafiq', 'Jubayer',
        'Mehedi', 'Sabbir', 'Tarek', 'Habib', 'Rashed', 'Ashik', 'Mamun', 'Shuvo', 'Rifat', 'Pritam', 'Sourav', 'Arif',
        'Nafis', 'Ishraq', 'Zahid', 'Tausif', 'Rumman', 'Anik', 'Sajjad', 'Kawsar'];

    private const LAST = ['Hossain', 'Rahman', 'Islam', 'Ahmed', 'Karim', 'Uddin', 'Chowdhury', 'Sarker', 'Mia', 'Alam',
        'Haque', 'Kabir', 'Biswas', 'Das', 'Roy', 'Talukder', 'Mollah', 'Bhuiyan'];

    // A few names in Bangla script to show Unicode support.
    private const BANGLA = ['তাসনিম রহমান', 'সাকিব হাসান', 'মেহেদী হাসান', 'অর্ণব দাস', 'ফারহান করিম'];

    private int $serial = 0;

    public function run(SeatAllocationService $allocations): void
    {
        $office = User::where('email', 'office@kjh.test')->firstOrFail();
        // Take seats from North, South, Extension in turn (every other seat, so rooms are not all full).
        // North comes first so the demo student lands in the demo tutor's block.
        $byBlock = Seat::with('room.block')->get()
            ->sortBy(fn ($seat) => [$seat->room->room_no, $seat->label])
            ->groupBy(fn ($seat) => $seat->room->block->name)
            ->map(fn ($seats) => $seats->values()->filter(fn ($seat, $i) => $i % 2 === 0)->values());
        $freeSeats = collect();
        for ($i = 0; $freeSeats->count() < 45; $i++) {
            foreach (['North', 'South', 'Extension'] as $block) {
                if (isset($byBlock[$block][$i])) {
                    $freeSeats->push($byBlock[$block][$i]);
                }
            }
        }

        // Residents.
        $residents = [
            $this->student('Rahim Uddin', 'student@student.du.test', '2021331001', 'Physics', '2021-22'),
            $this->student('Nafis Iqbal', 'clubadmin@student.du.test', '2020331002', 'Economics', '2020-21'),
        ];
        for ($i = 0; $i < 38; $i++) {
            $residents[] = $this->student();
        }
        foreach ($residents as $k => $profile) {
            DatabaseSeeder::travelTo(300 - $k * 5);
            $allocations->allocate($profile, $freeSeats[$k], $office);
        }

        // Attached: 3 former residents with history, 12 who never had a seat.
        for ($i = 0; $i < 15; $i++) {
            $profile = $this->student();
            if ($i < 3) {
                DatabaseSeeder::travelTo(380);
                $allocation = $allocations->allocate($profile, $freeSeats[40 + $i], $office);
                DatabaseSeeder::travelTo(60 - $i * 10);
                $allocations->vacate($allocation, today(), ['Moved to family home', 'Hall rules violation', 'Took a private mess'][$i], $office);
            }
        }

        DatabaseSeeder::travelTo(3);
        for ($i = 0; $i < 5; $i++) {
            $this->student(status: UserStatus::Pending);
        }

        DatabaseSeeder::travelTo(20);
        $rejected = $this->student(status: UserStatus::Rejected);
        $rejected->user->forceFill(['rejection_reason' => 'Not a student of this hall'])->save();
    }

    private function student(?string $name = null, ?string $email = null, ?string $regNo = null, ?string $department = null, ?string $session = null, UserStatus $status = UserStatus::Active): StudentProfile
    {
        $n = $this->serial++;
        $start = 2019 + $n % 6;
        $regNo ??= $start.'33'.str_pad((string) (1000 + $n), 4, '0', STR_PAD_LEFT);
        $name ??= $n % 13 === 7
            ? self::BANGLA[intdiv($n, 13) % count(self::BANGLA)]
            : self::FIRST[$n % count(self::FIRST)].' '.self::LAST[($n * 7) % count(self::LAST)];

        // Hash once: bcrypt for 60 users is slow, and the 'hashed' cast keeps an already-hashed value.
        static $password = null;
        $password ??= Hash::make('password');

        $user = new User(['name' => $name, 'email' => $email ?? "s{$regNo}@student.du.test", 'password' => $password]);
        $user->status = $status;
        $user->email_verified_at = now();
        $user->save();
        $user->assignRole(RoleName::Student);

        return $user->studentProfile()->create([
            'registration_no' => $regNo,
            'department' => $department ?? config('hall.departments')[($n * 5) % count(config('hall.departments'))],
            'session' => $session ?? sprintf('%d-%02d', $start, ($start + 1) % 100),
            'phone' => '017'.str_pad((string) (20000000 + $n * 7919), 8, '0', STR_PAD_LEFT),
        ]);
    }
}
