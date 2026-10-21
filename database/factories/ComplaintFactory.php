<?php

namespace Database\Factories;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * For tests and seeding only. Real complaints go through ComplaintService so they get a counter-issued tracking ID.
 *
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tracking_id' => sprintf('KJH-%d-%04d', now()->year, fake()->unique()->numberBetween(5000, 9999)),
            'student_profile_id' => fn () => User::factory()->student()->create()->studentProfile->id,
            'location_text' => 'Common room',
            'category' => fake()->randomElement(ComplaintCategory::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => ComplaintStatus::Submitted,
        ];
    }

    public function status(ComplaintStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'resolved_at' => in_array($status, [ComplaintStatus::Resolved, ComplaintStatus::Closed], true) ? now() : null,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['assigned_to' => $user->id]);
    }
}
