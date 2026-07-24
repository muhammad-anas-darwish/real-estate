<?php

namespace Modules\Deposit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Deposit\Entities\Deposit;
use Modules\Deposit\Enums\DepositStatus;
use Modules\RealEstate\Entities\Property;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Deposit\Entities\Deposit>
 */
class DepositFactory extends Factory
{
    protected $model = Deposit::class;

    public function definition(): array
    {
        return [
            'reference_number' => 'DEP-'.now()->format('Ymd').'-'.sprintf('%04d', fake()->unique()->numberBetween(1, 9999)),
            'property_id' => Property::factory()->approved(),
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 1000, 100000),
            'currency' => 'SAR',
            'status' => DepositStatus::PENDING,
            'terms' => fake()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepositStatus::PENDING,
        ]);
    }

    public function held(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepositStatus::HELD,
            'held_at' => now(),
        ]);
    }

    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepositStatus::RELEASED,
            'held_at' => now()->subDays(3),
            'released_at' => now(),
            'released_by' => $attributes['seller_id'] ?? User::factory(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepositStatus::REFUNDED,
            'held_at' => now()->subDays(3),
            'refunded_at' => now(),
            'refunded_by' => User::factory(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepositStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => User::factory(),
            'cancellation_reason' => 'Test cancellation',
        ]);
    }
}
