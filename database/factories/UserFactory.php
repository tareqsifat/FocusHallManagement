<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Pending]);
    }

    public function withRole(RoleName ...$roles): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(...$roles));
    }

    /** A student account with a profile and a @student.du.test email. */
    public function student(): static
    {
        return $this->state(fn () => ['email' => fake()->unique()->userName().'@student.du.test'])
            ->has(StudentProfile::factory())
            ->withRole(RoleName::Student);
    }
}
