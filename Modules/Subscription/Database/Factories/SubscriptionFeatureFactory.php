<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\Entities\SubscriptionFeature;
use Modules\Subscription\Enums\FeatureType;

class SubscriptionFeatureFactory extends Factory
{
    protected $model = SubscriptionFeature::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'type' => fake()->randomElement(FeatureType::cases()),
            'description' => fake()->sentence(),
        ];
    }

    public function toggle(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FeatureType::TOGGLE,
        ]);
    }

    public function limit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FeatureType::LIMIT,
        ]);
    }
}
