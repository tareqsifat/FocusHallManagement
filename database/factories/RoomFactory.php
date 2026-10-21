<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates the room with one seat per capacity slot (A, B, C...).
 *
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'block_id' => Block::factory(),
            'room_no' => (string) fake()->unique()->numberBetween(100, 999),
            'floor' => fake()->numberBetween(1, 5),
            'capacity' => 2,
        ];
    }

    public function capacity(int $capacity): static
    {
        return $this->state(fn () => ['capacity' => $capacity]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Room $room) {
            foreach (array_slice(range('A', 'Z'), 0, $room->capacity) as $label) {
                $room->seats()->create(['label' => $label]);
            }
        });
    }
}
