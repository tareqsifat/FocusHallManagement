<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\HouseTutor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseTutor>
 */
class HouseTutorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->withRole(RoleName::HouseTutor),
            'designation' => fake()->randomElement(['Lecturer', 'Assistant Professor', 'Associate Professor', 'Professor']),
            'department' => fake()->randomElement(config('hall.departments')),
            'phone' => '017'.fake()->numerify('########'),
        ];
    }
}
