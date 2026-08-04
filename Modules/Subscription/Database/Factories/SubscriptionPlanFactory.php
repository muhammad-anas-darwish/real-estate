<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\Entities\SubscriptionPlan;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 9.99, 199.99),
            'currency' => 'USD',
            'duration_days' => fake()->randomElement([30, 60, 90, 180, 365]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
