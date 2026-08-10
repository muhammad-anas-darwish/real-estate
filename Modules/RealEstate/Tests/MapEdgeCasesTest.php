<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Services\MapFilterService;
use Modules\RealEstate\ValueObjects\MapBounds;
use Tests\TestCase;

class MapEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_empty_database_returns_zero(): void
    {
        $response = $this->getJson('/api/map/properties');
        $this->assertCount(0, $response->json('data.properties'));
        $this->assertEquals(0, $response->json('data.meta.total_matching'));
        $this->assertFalse($response->json('data.meta.is_truncated'));
    }

    public function test_all_properties_without_coordinates_excluded(): void
    {
        Property::factory()->count(5)->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => null, 'longitude' => null,
        ]);

        $response = $this->getJson('/api/map/properties');
        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_filter_returns_zero_when_no_match(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'price' => 100000,
        ]);

        $response = $this->getJson('/api/map/properties?price_min=500000');
        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_radius_small_returns_nothing_for_distant_property(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 25.0, 'longitude' => 47.0,  // ~50 km away
        ]);

        $response = $this->getJson(
            '/api/map/properties?center_lat=24.7&center_lng=46.7&radius_km=0.1'
        );
        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_count_in_bounds_helper(): void
    {
        $bounds = new MapBounds(24.0, 46.0, 25.0, 47.0);

        Property::factory()->count(3)->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.5, 'longitude' => 46.5,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 30.0, 'longitude' => 50.0,
        ]);

        $service = new MapFilterService;
        $count = $service->countInBounds($bounds);
        $this->assertEquals(3, $count);
    }

    public function test_suggest_broader_bounds_when_truncated(): void
    {
        $bounds = new MapBounds(24.0, 46.0, 25.0, 47.0);
        $service = new MapFilterService;

        $this->assertNull($service->suggestBroaderBounds($bounds, 100));

        $broader = $service->suggestBroaderBounds($bounds, 600);
        $this->assertNotNull($broader);
        $this->assertLessThan($bounds->swLat, $broader->swLat);
        $this->assertGreaterThan($bounds->neLat, $broader->neLat);
    }
}
