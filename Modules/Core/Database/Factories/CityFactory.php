<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Core\SubModules\Location\Entities\City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city(),
            'country_id' => fake()->randomElement(Country::all()->toArray()),
            'state_province' => fake()->randomElement(['state', 'province', 'region']),
            'postal_code' => fake()->postcode(),
            'is_active' => fake()->boolean(),
        ];
    }
}
