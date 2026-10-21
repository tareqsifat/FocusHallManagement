<?php

namespace App\Services;

use App\Enums\ResidencyStatus;
use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeatAllocationService
{
    /**
     * BR-01, BR-02, BR-03. The student and seat rows are locked so two concurrent requests
     * cannot both pass the checks; the unique active_* columns catch anything that slips through.
     */
    public function allocate(StudentProfile $student, Seat $seat, User $by): SeatAllocation
    {
        try {
            return DB::transaction(function () use ($student, $seat, $by) {
                $student = StudentProfile::with('user')->lockForUpdate()->findOrFail($student->id);
                $seat = Seat::lockForUpdate()->findOrFail($seat->id);

                if (! $student->user->isActive()) {
                    $this->fail('seat_id', 'Only approved students can be allocated a seat.');
                }
                if (! $seat->is_active) {
                    $this->fail('seat_id', 'This seat has been retired from its room.');
                }
                if ($student->currentAllocation()->exists()) {
                    $this->fail('seat_id', 'This student already holds a seat. Vacate it first.');
                }
                if ($seat->activeAllocation()->exists()) {
                    $this->fail('seat_id', 'This seat is already occupied.');
                }

                $allocation = new SeatAllocation;
                $allocation->seat_id = $seat->id;
                $allocation->student_profile_id = $student->id;
                $allocation->allocated_at = today();
                $allocation->allocated_by = $by->id;
                $allocation->save();

                $student->residency_status = ResidencyStatus::Resident;
                $student->save();

                return $allocation;
            });
        } catch (UniqueConstraintViolationException) {
            $this->fail('seat_id', 'The seat or student was allocated by someone else just now. Please reload and try again.');
        }
    }

    /** BR-04: ends the allocation and returns the student to Attached. The row is kept as history. */
    public function vacate(SeatAllocation $allocation, Carbon $vacatedAt, string $reason, User $by): SeatAllocation
    {
        return DB::transaction(function () use ($allocation, $vacatedAt, $reason, $by) {
            $allocation = SeatAllocation::with('student')->lockForUpdate()->findOrFail($allocation->id);

            if (! $allocation->isActive()) {
                $this->fail('vacated_at', 'This allocation has already been vacated.');
            }
            if ($vacatedAt->lt($allocation->allocated_at)) {
                $this->fail('vacated_at', 'The vacate date cannot be before the allocation date ('.$allocation->allocated_at->toDateString().').');
            }

            $allocation->vacated_at = $vacatedAt;
            $allocation->vacate_reason = $reason;
            $allocation->vacated_by = $by->id;
            $allocation->save();

            $allocation->student->residency_status = ResidencyStatus::Attached;
            $allocation->student->save();

            return $allocation;
        });
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
