<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdView;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\RealEstate\Entities\AdView>
 */
class AdViewFactory extends Factory
{
    protected $model = AdView::class;

    public function definition(): array
    {
        return [
            'ad_id' => Ad::factory(),
            'user_id' => User::factory(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'viewed_at' => now(),
        ];
    }
}
