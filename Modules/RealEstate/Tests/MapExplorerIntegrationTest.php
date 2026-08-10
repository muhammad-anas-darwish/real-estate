<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;
use Tests\TestCase;

class MapExplorerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_response_includes_required_fields_for_frontend(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7,
            'longitude' => 46.7,
            'price' => 250000,
            'currency' => 'SAR',
            'rooms' => 3,
            'area' => 150,
        ]);

        $response = $this->getJson('/api/map/properties');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'properties' => [
                    '*' => [
                        'id', 'name', 'latitude', 'longitude',
                        'price', 'currency', 'rooms', 'area',
                        'main_image', 'city_id', 'city_name',
                    ],
                ],
                'meta' => ['total_matching', 'returned', 'is_truncated', 'limit'],
                'filter',
            ],
        ]);
    }

    public function test_filter_combinations(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'property_type' => PropertyType::Apartment,
            'type_of_contract' => TypeOfContract::Sale,
            'price' => 200000, 'rooms' => 2,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.8, 'longitude' => 46.8,
            'property_type' => PropertyType::Villa,
            'type_of_contract' => TypeOfContract::Rent,
            'price' => 800000, 'rooms' => 5,
        ]);

        $response = $this->getJson(
            '/api/map/properties?property_type=apartment&contract_type=sale&price_max=300000&rooms_min=2'
        );

        $this->assertCount(1, $response->json('data.properties'));
    }

    public function test_bounds_filter(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 30.0, 'longitude' => 50.0,
        ]);

        $response = $this->getJson(
            '/api/map/properties?sw_lat=24.0&sw_lng=46.0&ne_lat=25.0&ne_lng=47.0'
        );

        $this->assertCount(1, $response->json('data.properties'));
    }

    public function test_radius_filter(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 30.0, 'longitude' => 50.0,
        ]);

        $response = $this->getJson(
            '/api/map/properties?center_lat=24.7&center_lng=46.7&radius_km=50'
        );

        $this->assertCount(1, $response->json('data.properties'));
    }

    public function test_city_filter(): void
    {
        $city = City::first();
        $otherCity = City::factory()->create(['country_id' => $city->country_id]);

        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'city_id' => $city->id,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.8, 'longitude' => 46.8,
            'city_id' => $otherCity->id,
        ]);

        $response = $this->getJson("/api/map/properties?city_id={$city->id}");

        $this->assertCount(1, $response->json('data.properties'));
    }

    public function test_created_within_days_filter(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'created_at' => now()->subDays(2),
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.8, 'longitude' => 46.8,
            'created_at' => now()->subDays(60),
        ]);

        $response = $this->getJson('/api/map/properties?created_within_days=7');

        $this->assertCount(1, $response->json('data.properties'));
    }
}
