<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Enums\AdMediaType;
use Modules\RealEstate\Enums\AdStatus;

class AdFactory extends Factory
{
    protected $model = Ad::class;

    public function definition(): array
    {
        return [
            'ad_group_id' => AdGroup::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'media_type' => AdMediaType::IMAGE,
            'external_url' => fake()->optional()->url(),
            'property_id' => null,
            'status' => AdStatus::DRAFT,
            'is_default' => false,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => null,
            'created_by' => User::factory(),
        ];
    }
}
