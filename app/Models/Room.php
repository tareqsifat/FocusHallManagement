<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    // capacity is not fillable: RoomService keeps it in sync with the active seats.
    protected $fillable = ['block_id', 'room_no', 'floor'];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    /** Adds occupied_count (seats with an active allocation). */
    public function scopeWithOccupancy(Builder $query): void
    {
        $query->withCount(['seats as occupied_count' => fn ($q) => $q->whereHas('activeAllocation')]);
    }

    public function label(): string
    {
        return "{$this->block->name} {$this->room_no}";
    }
}
