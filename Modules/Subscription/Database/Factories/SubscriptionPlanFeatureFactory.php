<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\Entities\SubscriptionFeature;
use Modules\Subscription\Entities\SubscriptionPlan;
use Modules\Subscription\Entities\SubscriptionPlanFeature;

class SubscriptionPlanFeatureFactory extends Factory
{
    protected $model = SubscriptionPlanFeature::class;

    public function definition(): array
    {
        return [
            'plan_id' => SubscriptionPlan::factory(),
            'feature_id' => SubscriptionFeature::factory(),
            'is_enabled' => fake()->boolean(),
            'limit_value' => null,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    public function withLimit(int $limit = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => true,
            'limit_value' => $limit,
        ]);
    }
}
