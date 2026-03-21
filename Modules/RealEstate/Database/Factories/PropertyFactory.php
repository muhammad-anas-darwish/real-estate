<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\RealEstate\Entities\Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country = Country::inRandomOrder()->first();
        $city = City::where('country_id', $country->id)->inRandomOrder()->first();

        return [
            'name' => fake()->randomElement([
                'Modern Apartment',
                'Luxury Villa',
                'Cozy Studio',
                'Family House',
                'Penthouse Suite',
                'Beach House',
                'Mountain Retreat',
                'City Loft',
                'Country Estate',
                'Urban Condo',
            ]) . ' in ' . $city->name,
            'description' => fake()->paragraphs(3, true),
            'country_id' => $country->id,
            'city_id' => $city->id,
            'longitude' => fake()->longitude(),
            'latitude' => fake()->latitude(),
            'rooms' => fake()->numberBetween(1, 10),
            'bathrooms' => fake()->numberBetween(1, 5),
            'area' => fake()->randomFloat(2, 30, 500),
            'detailed_info' => fake()->paragraphs(5, true),
            'price' => fake()->randomFloat(2, 50000, 5000000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'CAD', 'AUD']),
            'publisher_id' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'status' => 'pending',
            'property_type'    => fake()->randomElement(PropertyType::cases()),
            'type_of_contract' => fake()->randomElement(TypeOfContract::cases()),
        ];
    }

    /**
     * Indicate that the property is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the property is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the property is sold.
     */
    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sold',
            'approved_by' => User::factory(),
            'approved_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Set a specific publisher.
     */
    public function publishedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'publisher_id' => $user->id,
        ]);
    }

    /**
     * Set a specific country and a random city within it.
     */
    public function inCountry(Country $country): static
    {
        $city = City::where('country_id', $country->id)->inRandomOrder()->first();

        return $this->state(fn (array $attributes) => [
            'country_id' => $country->id,
            'city_id' => $city->id,
        ]);
    }

    /**
     * Set a specific city (and its country).
     */
    public function inCity(City $city): static
    {
        return $this->state(fn (array $attributes) => [
            'country_id' => $city->country_id,
            'city_id' => $city->id,
        ]);
    }
}
