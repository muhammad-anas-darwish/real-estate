<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\AdGroup;

class AdGroupFactory extends Factory
{
    protected $model = AdGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'description' => fake()->sentence(),
            'status' => 'active',
            'is_archived' => false,
            'created_by' => User::factory(),
        ];
    }
}
