<?php

namespace Modules\Core\Category\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Category\Entities\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Property categories
        $propertyCategories = [
            'Apartment',
            'Villa',
            'House',
            'Studio',
            'Penthouse',
            'Townhouse',
            'Duplex',
            'Loft',
        ];

        foreach ($propertyCategories as $name) {
            Category::create([
                'name' => $name,
                'type' => 'property',
            ]);
        }

        // Car categories
        $carCategories = [
            'Sedan',
            'SUV',
            'Truck',
            'Coupe',
            'Convertible',
            'Hatchback',
            'Sports Car',
            'Electric',
        ];

        foreach ($carCategories as $name) {
            Category::create([
                'name' => $name,
                'type' => 'car',
            ]);
        }

        $this->command->info('CategorySeeder: '.count($propertyCategories).' property categories and '.count($carCategories).' car categories seeded successfully!');
    }
}
