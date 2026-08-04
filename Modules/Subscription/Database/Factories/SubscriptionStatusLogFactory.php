<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionStatusLog;

class SubscriptionStatusLogFactory extends Factory
{
    protected $model = SubscriptionStatusLog::class;

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'from_status' => 'pending',
            'to_status' => 'active',
            'reason' => fake()->sentence(),
        ];
    }
}
