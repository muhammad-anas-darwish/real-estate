<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyViewing;
use Modules\RealEstate\Enums\ViewingStatus;
use Modules\RealEstate\Enums\ViewingType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\RealEstate\Entities\PropertyViewing>
 */
class PropertyViewingFactory extends Factory
{
    protected $model = PropertyViewing::class;

    public function definition(): array
    {
        $scheduledAt = fake()->dateTimeBetween('+1 hour', '+14 days');

        return [
            'property_id' => Property::factory(),
            'user_id' => User::factory(),
            'agent_id' => User::factory(),
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => fake()->randomElement([30, 45, 60]),
            'buffer_minutes' => fake()->randomElement([0, 15, 30]),
            'status' => fake()->randomElement(ViewingStatus::values()),
            'viewing_type' => fake()->randomElement(ViewingType::values()),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
            'agent_notes' => fake()->optional()->sentence(),
            'max_attendees' => fake()->optional()->numberBetween(1, 10),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ViewingStatus::PENDING,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ViewingStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ViewingStatus::COMPLETED,
            'confirmed_at' => now()->subHours(2),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ViewingStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function forProperty(Property $property): static
    {
        return $this->state(fn (array $attributes) => [
            'property_id' => $property->id,
        ]);
    }

    public function forAgent(User $agent): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_id' => $agent->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
