<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Auth\Database\Seeders\UserSeeder;
use Modules\Core\Database\Seeders\CountriesAndCitiesSeeder;
use Modules\RealEstate\Database\Seeders\PropertySeeder;
use Modules\Subscription\Database\Seeders\SubscriptionSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            UserSeeder::class,
            CountriesAndCitiesSeeder::class,
            PropertySeeder::class,
            SubscriptionSeeder::class,
        ]);
    }
}
