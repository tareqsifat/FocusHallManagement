<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->numberBetween(2019, 2025);

        return [
            'registration_no' => (string) fake()->unique()->numberBetween(2019000000, 2025999999),
            'department' => fake()->randomElement(config('hall.departments')),
            'session' => sprintf('%d-%02d', $start, ($start + 1) % 100),
            'phone' => '01'.fake()->randomElement(['3', '5', '7', '8', '9']).fake()->numerify('########'),
        ];
    }
}
