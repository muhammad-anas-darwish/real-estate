<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Expert\Entities\ExpertRequest;
use Modules\RealEstate\Expert\Enums\ExpertRequestStatus;
use Modules\RealEstate\Expert\Enums\ExpertType;

class ExpertRequestFactory extends Factory
{
    protected $model = ExpertRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expert_type' => $this->faker->randomElement(ExpertType::cases()),
            'message' => $this->faker->sentence(),
            'status' => ExpertRequestStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpertRequestStatus::Pending,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpertRequestStatus::Resolved,
        ]);
    }
}
