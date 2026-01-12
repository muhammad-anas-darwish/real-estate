<?php

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create users for seeding
        $publisher = User::first() ?? User::factory()->create();
        $approver = User::skip(1)->first() ?? User::factory()->create();

        // Create pending properties
        Property::factory()
            ->count(5)
            ->publishedBy($publisher)
            ->create();

        // Create approved properties
        Property::factory()
            ->count(10)
            ->approved()
            ->publishedBy($publisher)
            ->create([
                'approved_by' => $approver->id,
            ]);

        // Create rejected properties
        Property::factory()
            ->count(2)
            ->rejected()
            ->publishedBy($publisher)
            ->create([
                'approved_by' => $approver->id,
            ]);

        // Create sold properties
        Property::factory()
            ->count(3)
            ->sold()
            ->publishedBy($publisher)
            ->create([
                'approved_by' => $approver->id,
            ]);

        $this->command->info('PropertySeeder: 20 properties seeded successfully!');
    }
}
