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
                    ['name' => 'New York', 'state_province' => 'New York', 'latitude' => 40.7128, 'longitude' => -74.0060],
                    ['name' => 'Los Angeles', 'state_province' => 'California', 'latitude' => 34.0522, 'longitude' => -118.2437],
                    ['name' => 'Chicago', 'state_province' => 'Illinois', 'latitude' => 41.8781, 'longitude' => -87.6298],
                    ['name' => 'Houston', 'state_province' => 'Texas', 'latitude' => 29.7604, 'longitude' => -95.3698],
                    ['name' => 'Miami', 'state_province' => 'Florida', 'latitude' => 25.7617, 'longitude' => -80.1918],
                    ['name' => 'San Francisco', 'state_province' => 'California', 'latitude' => 37.7749, 'longitude' => -122.4194],
                    ['name' => 'Seattle', 'state_province' => 'Washington', 'latitude' => 47.6062, 'longitude' => -122.3321],
                    ['name' => 'Boston', 'state_province' => 'Massachusetts', 'latitude' => 42.3601, 'longitude' => -71.0589],
                ]
            ],
            [
                'name' => 'United Kingdom',
                'code' => 'GBR',
                'phone_code' => '+44',
                'cities' => [
                    ['name' => 'London', 'state_province' => 'England', 'latitude' => 51.5074, 'longitude' => -0.1278],
                    ['name' => 'Manchester', 'state_province' => 'England', 'latitude' => 53.4808, 'longitude' => -2.2426],
                    ['name' => 'Birmingham', 'state_province' => 'England', 'latitude' => 52.4862, 'longitude' => -1.8904],
                    ['name' => 'Edinburgh', 'state_province' => 'Scotland', 'latitude' => 55.9533, 'longitude' => -3.1883],
                    ['name' => 'Liverpool', 'state_province' => 'England', 'latitude' => 53.4084, 'longitude' => -2.9916],
                ]
            ],
            [
                'name' => 'Canada',
                'code' => 'CAN',
                'phone_code' => '+1',
                'cities' => [
                    ['name' => 'Toronto', 'state_province' => 'Ontario', 'latitude' => 43.6532, 'longitude' => -79.3832],
                    ['name' => 'Vancouver', 'state_province' => 'British Columbia', 'latitude' => 49.2827, 'longitude' => -123.1207],
                    ['name' => 'Montreal', 'state_province' => 'Quebec', 'latitude' => 45.5017, 'longitude' => -73.5673],
                    ['name' => 'Calgary', 'state_province' => 'Alberta', 'latitude' => 51.0447, 'longitude' => -114.0719],
                ]
            ],
            [
                'name' => 'Australia',
                'code' => 'AUS',
                'phone_code' => '+61',
                'cities' => [
                    ['name' => 'Sydney', 'state_province' => 'New South Wales', 'latitude' => -33.8688, 'longitude' => 151.2093],
                    ['name' => 'Melbourne', 'state_province' => 'Victoria', 'latitude' => -37.8136, 'longitude' => 144.9631],
                    ['name' => 'Brisbane', 'state_province' => 'Queensland', 'latitude' => -27.4698, 'longitude' => 153.0251],
                    ['name' => 'Perth', 'state_province' => 'Western Australia', 'latitude' => -31.9505, 'longitude' => 115.8605],
                ]
            ],
            [
                'name' => 'Germany',
                'code' => 'DEU',
                'phone_code' => '+49',
                'cities' => [
                    ['name' => 'Berlin', 'state_province' => 'Berlin', 'latitude' => 52.5200, 'longitude' => 13.4050],
                    ['name' => 'Munich', 'state_province' => 'Bavaria', 'latitude' => 48.1351, 'longitude' => 11.5820],
                    ['name' => 'Frankfurt', 'state_province' => 'Hesse', 'latitude' => 50.1109, 'longitude' => 8.6821],
                    ['name' => 'Hamburg', 'state_province' => 'Hamburg', 'latitude' => 53.5511, 'longitude' => 9.9937],
                ]
            ],
            [
                'name' => 'France',
                'code' => 'FRA',
                'phone_code' => '+33',
                'cities' => [
                    ['name' => 'Paris', 'state_province' => 'Île-de-France', 'latitude' => 48.8566, 'longitude' => 2.3522],
                    ['name' => 'Lyon', 'state_province' => 'Auvergne-Rhône-Alpes', 'latitude' => 45.7640, 'longitude' => 4.8357],
                    ['name' => 'Marseille', 'state_province' => 'Provence-Alpes-Côte d\'Azur', 'latitude' => 43.2965, 'longitude' => 5.3698],
                    ['name' => 'Nice', 'state_province' => 'Provence-Alpes-Côte d\'Azur', 'latitude' => 43.7102, 'longitude' => 7.2620],
                ]
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
