<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\Entities\StripeEvent;

class StripeEventFactory extends Factory
{
    protected $model = StripeEvent::class;

    public function definition(): array
    {
        return [
            'stripe_event_id' => 'evt_'.fake()->unique()->regexify('[a-zA-Z0-9]{20}'),
            'type' => fake()->randomElement([
                'checkout.session.completed',
                'checkout.session.expired',
                'charge.refunded',
            ]),
            'status' => fake()->randomElement(['pending', 'processing', 'processed', 'failed']),
            'payload' => [],
            'error_message' => null,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
        ]);
    }

    public function failed(string $message = 'Processing error'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }
}
