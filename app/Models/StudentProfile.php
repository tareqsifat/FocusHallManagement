<?php

namespace App\Models;

use App\Enums\ResidencyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = ['registration_no', 'department', 'session', 'phone'];

    // residency_status is not fillable: only SeatAllocationService changes it.
    protected $attributes = ['residency_status' => 'attached'];

    protected function casts(): array
    {
        return ['residency_status' => ResidencyStatus::class];
    }

    /**
     * Hall admins see every student; a house tutor sees residents of their blocks (BR-08);
     * anyone else sees only their own profile.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isHallAdmin()) {
            return;
        }

        $query->where(fn ($q) => $q
            ->where('user_id', $user->id)
            ->orWhereHas('currentAllocation.seat.room', fn ($r) => $r->whereIn('block_id', $user->tutorBlockIds())));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SeatAllocation::class);
    }

    public function clubMemberships(): HasMany
    {
        return $this->hasMany(ClubMembership::class);
    }

    public function currentAllocation(): HasOne
    {
        return $this->hasOne(SeatAllocation::class)->whereNull('vacated_at');
    }
}
