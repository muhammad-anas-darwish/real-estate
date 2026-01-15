<?php

namespace Modules\Core\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Category\Entities\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Core\Category\Entities\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $propertyCategories = [
            'Apartment',
            'Villa',
            'House',
            'Studio',
            'Penthouse',
            'Townhouse',
            'Duplex',
            'Loft',
            'Cottage',
            'Mansion',
        ];

        $carCategories = [
            'Sedan',
            'SUV',
            'Truck',
            'Coupe',
            'Convertible',
            'Hatchback',
            'Wagon',
            'Van',
            'Sports Car',
            'Electric',
        ];

        $type = fake()->randomElement(['property', 'car']);
        $categories = $type === 'property' ? $propertyCategories : $carCategories;

        return [
            'name' => fake()->unique()->randomElement($categories),
            'type' => $type,
        ];
    }

    /**
     * Indicate that the category is for properties.
     */
    public function property(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'property',
        ]);
    }

    /**
     * Indicate that the category is for cars.
     */
    public function car(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'car',
        ]);
    }
}
