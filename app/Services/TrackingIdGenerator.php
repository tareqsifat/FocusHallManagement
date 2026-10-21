<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Issues KJH-YYYY-NNNN tracking IDs (BR-09) from a per-year counter row. The sequence
 * restarts each year because each year has its own row.
 *
 * Call it before opening a transaction: the year row is created with an
 * auto-committed INSERT IGNORE (inside a transaction its shared lock would deadlock
 * against concurrent FOR UPDATE reads), then incremented under an exclusive row lock in
 * a short transaction of its own. If the caller later fails, that number is skipped:
 * the sequence may have gaps but never duplicates.
 */
class TrackingIdGenerator
{
    public function next(): string
    {
        $year = now()->year;
        DB::table('complaint_counters')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

        $number = DB::transaction(function () use ($year) {
            $number = DB::table('complaint_counters')->where('year', $year)->lockForUpdate()->value('last_number') + 1;
            DB::table('complaint_counters')->where('year', $year)->update(['last_number' => $number]);

            return $number;
        });

        return sprintf('%s-%d-%04d', config('hall.tracking_prefix'), $year, $number);
    }
}
