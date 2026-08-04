<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\Entities\SubscriptionDiscount;
use Modules\Subscription\Enums\DiscountType;

class SubscriptionDiscountFactory extends Factory
{
    protected $model = SubscriptionDiscount::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('DISCOUNT-??????'),
            'type' => fake()->randomElement(DiscountType::cases()),
            'value' => fake()->randomFloat(2, 5, 50),
            'plan_id' => null,
            'max_uses' => fake()->optional(0.7)->numberBetween(10, 100),
            'expires_at' => fake()->optional(0.3)->dateTimeBetween('+1 month', '+6 months'),
            'is_active' => true,
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::PERCENTAGE,
            'value' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::FIXED,
            'value' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => 1,
            'used_count' => 1,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
