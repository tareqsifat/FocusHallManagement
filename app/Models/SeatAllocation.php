<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Allocation history. Rows are never deleted (BR-04); vacating sets vacated_at.
 * All writes go through SeatAllocationService, so nothing is mass assignable.
 */
class SeatAllocation extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'allocated_at' => 'date',
            'vacated_at' => 'date',
        ];
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function vacatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vacated_by');
    }

    public function isActive(): bool
    {
        return $this->vacated_at === null;
    }
}
