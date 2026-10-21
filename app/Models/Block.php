<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Block extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function houseTutors(): BelongsToMany
    {
        return $this->belongsToMany(HouseTutor::class);
    }

    public function seats(): HasManyThrough
    {
        return $this->hasManyThrough(Seat::class, Room::class);
    }
}
