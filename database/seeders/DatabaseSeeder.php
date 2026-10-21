<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Section 10 demo data. Everything is fictional. Records are created through the real
 * services (seat allocation, tracking IDs, status transitions, memberships) with the clock
 * moved back, so history, overdue flags and dashboard figures are internally consistent.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        fake()->seed(2026);

        try {
            $this->call([
                HallSeeder::class,
                StudentSeeder::class,
                ClubSeeder::class,
                ComplaintSeeder::class,
                NoticeSeeder::class,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /** Move the application clock to N days before the real "now" (seeders only). */
    public static function travelTo(int $daysAgo, int $hour = 10, int $minute = 0): void
    {
        Carbon::setTestNow();
        Carbon::setTestNow(now()->subDays($daysAgo)->setTime($hour, $minute));
    }
}
