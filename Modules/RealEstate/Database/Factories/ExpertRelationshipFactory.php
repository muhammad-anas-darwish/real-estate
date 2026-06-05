<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\RealEstate\Expert\Entities\ExpertRelationship;
use Modules\RealEstate\Expert\Enums\ExpertRelationshipStatus;

class ExpertRelationshipFactory extends Factory
{
    protected $model = ExpertRelationship::class;

    public function definition(): array
    {
        return [
            'expert_id' => User::factory()->create(['is_expert' => true]),
            'user_id' => User::factory()->create(['is_expert' => false]),
            'room_id' => ChatRoom::factory(),
            'status' => ExpertRelationshipStatus::Active,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpertRelationshipStatus::Active,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpertRelationshipStatus::Cancelled,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpertRelationshipStatus::Completed,
        ]);
    }
}
