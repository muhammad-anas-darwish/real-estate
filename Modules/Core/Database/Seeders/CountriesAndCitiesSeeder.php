<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\SubModules\Location\Entities\Country;

class CountriesAndCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'name' => 'United States',
                'code' => 'USA',
                'phone_code' => '+1',
                'cities' => [
                    ['name' => 'New York', 'state_province' => 'New York'],
                    ['name' => 'Los Angeles', 'state_province' => 'California'],
                    ['name' => 'Chicago', 'state_province' => 'Illinois'],
                    ['name' => 'Houston', 'state_province' => 'Texas'],
                    ['name' => 'Miami', 'state_province' => 'Florida'],
                    ['name' => 'San Francisco', 'state_province' => 'California'],
                    ['name' => 'Seattle', 'state_province' => 'Washington'],
                    ['name' => 'Boston', 'state_province' => 'Massachusetts'],
                ],
            ],
            [
                'name' => 'United Kingdom',
                'code' => 'GBR',
                'phone_code' => '+44',
                'cities' => [
                    ['name' => 'London', 'state_province' => 'England'],
                    ['name' => 'Manchester', 'state_province' => 'England'],
                    ['name' => 'Birmingham', 'state_province' => 'England'],
                    ['name' => 'Edinburgh', 'state_province' => 'Scotland'],
                    ['name' => 'Liverpool', 'state_province' => 'England'],
                ],
            ],
            [
                'name' => 'Canada',
                'code' => 'CAN',
                'phone_code' => '+1',
                'cities' => [
                    ['name' => 'Toronto', 'state_province' => 'Ontario'],
                    ['name' => 'Vancouver', 'state_province' => 'British Columbia'],
                    ['name' => 'Montreal', 'state_province' => 'Quebec'],
                    ['name' => 'Calgary', 'state_province' => 'Alberta'],
                ],
            ],
            [
                'name' => 'Australia',
                'code' => 'AUS',
                'phone_code' => '+61',
                'cities' => [
                    ['name' => 'Sydney', 'state_province' => 'New South Wales'],
                    ['name' => 'Melbourne', 'state_province' => 'Victoria'],
                    ['name' => 'Brisbane', 'state_province' => 'Queensland'],
                    ['name' => 'Perth', 'state_province' => 'Western Australia'],
                ],
            ],
            [
                'name' => 'Germany',
                'code' => 'DEU',
                'phone_code' => '+49',
                'cities' => [
                    ['name' => 'Berlin', 'state_province' => 'Berlin'],
                    ['name' => 'Munich', 'state_province' => 'Bavaria'],
                    ['name' => 'Frankfurt', 'state_province' => 'Hesse'],
                    ['name' => 'Hamburg', 'state_province' => 'Hamburg'],
                ],
            ],
            [
                'name' => 'France',
                'code' => 'FRA',
                'phone_code' => '+33',
                'cities' => [
                    ['name' => 'Paris', 'state_province' => 'Île-de-France'],
                    ['name' => 'Lyon', 'state_province' => 'Auvergne-Rhône-Alpes'],
                    ['name' => 'Marseille', 'state_province' => 'Provence-Alpes-Côte d\'Azur'],
                    ['name' => 'Nice', 'state_province' => 'Provence-Alpes-Côte d\'Azur'],
                ],
            ],
        ];

        foreach ($countries as $countryData) {
            $cities = $countryData['cities'];
            unset($countryData['cities']);

            $country = Country::create($countryData);

            foreach ($cities as $cityData) {
                $country->cities()->create($cityData);
            }
        }

        $this->command->info('Countries and cities seeded successfully!');
    }
}
