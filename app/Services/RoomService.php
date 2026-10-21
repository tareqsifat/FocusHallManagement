<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Keeps a room's seats in step with its capacity. Seats are never deleted because they
 * carry allocation history; lowering capacity retires free seats, raising it brings
 * retired seats back before creating new labels.
 */
class RoomService
{
    public function create(array $data): Room
    {
        return DB::transaction(function () use ($data) {
            $room = new Room($data);
            $room->capacity = $data['capacity'];
            $room->save();

            foreach (array_slice(range('A', 'Z'), 0, $room->capacity) as $label) {
                $room->seats()->create(['label' => $label]);
            }

            return $room;
        });
    }

    public function update(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {
            $room = Room::lockForUpdate()->findOrFail($room->id);
            $seats = $room->seats()->withExists('activeAllocation')->orderBy('label')->lockForUpdate()->get();
            $capacity = (int) $data['capacity'];
            $occupied = $seats->where('active_allocation_exists', true)->count();

            // BR-05
            if ($capacity < $occupied) {
                throw ValidationException::withMessages([
                    'capacity' => "Capacity cannot be lower than the {$occupied} currently occupied seats.",
                ]);
            }

            $active = $seats->where('is_active', true);
            if ($capacity < $active->count()) {
                $active->where('active_allocation_exists', false)
                    ->sortByDesc('label')
                    ->take($active->count() - $capacity)
                    ->each(fn ($seat) => $seat->update(['is_active' => false]));
            } elseif ($capacity > $active->count()) {
                $needed = $capacity - $active->count();
                $seats->where('is_active', false)->take($needed)->each(function ($seat) use (&$needed) {
                    $seat->update(['is_active' => true]);
                    $needed--;
                });
                $unused = array_diff(range('A', 'Z'), $seats->pluck('label')->all());
                foreach (array_slice($unused, 0, $needed) as $label) {
                    $room->seats()->create(['label' => $label]);
                }
            }

            $room->fill($data);
            $room->capacity = $capacity;
            $room->save();

            return $room;
        });
    }
}
