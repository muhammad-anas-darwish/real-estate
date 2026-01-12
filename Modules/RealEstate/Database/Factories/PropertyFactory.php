<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;

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
        $countries = ['United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Spain', 'Italy'];
        $cities = [
            'United States' => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Miami'],
            'United Kingdom' => ['London', 'Manchester', 'Birmingham', 'Liverpool', 'Edinburgh'],
            'Canada' => ['Toronto', 'Vancouver', 'Montreal', 'Calgary', 'Ottawa'],
            'Australia' => ['Sydney', 'Melbourne', 'Brisbane', 'Perth', 'Adelaide'],
            'Germany' => ['Berlin', 'Munich', 'Hamburg', 'Frankfurt', 'Cologne'],
            'France' => ['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice'],
            'Spain' => ['Madrid', 'Barcelona', 'Valencia', 'Seville', 'Bilbao'],
            'Italy' => ['Rome', 'Milan', 'Naples', 'Turin', 'Florence'],
        ];

        $country = fake()->randomElement($countries);
        $city = fake()->randomElement($cities[$country]);

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
            ]) . ' in ' . $city,
            'description' => fake()->paragraphs(3, true),
            'country' => $country,
            'city' => $city,
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
}
