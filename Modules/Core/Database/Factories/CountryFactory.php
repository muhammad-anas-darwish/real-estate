<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\SubModules\Location\Entities\Country;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Core\SubModules\Location\Entities\Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'code' => fake()->countryCode(),
            'phone_code' => '+'.fake()->randomNumber(2),
            'is_active' => fake()->boolean(),
        ];
    }
}
