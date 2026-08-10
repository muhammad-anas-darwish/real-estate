<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'properties.list', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo('properties.list');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_map_properties_is_public(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $response = $this->getJson('/api/map/properties');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.properties'));
    }

    public function test_excludes_pending_properties(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::PENDING,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $response = $this->getJson('/api/map/properties');

        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_excludes_properties_without_coordinates(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->getJson('/api/map/properties');
        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_filter_by_price_range(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7,
            'longitude' => 46.7,
            'price' => 100000,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7,
            'longitude' => 46.7,
            'price' => 500000,
        ]);

        $response = $this->getJson('/api/map/properties?price_min=200000&price_max=400000');

        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_truncation_message(): void
    {
        Property::factory()->count(10)->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7,
            'longitude' => 46.7,
        ]);

        $response = $this->getJson('/api/map/properties?limit=5');

        $this->assertTrue($response->json('data.meta.is_truncated'));
        $this->assertStringContainsString('5 من 10', $response->json('data.meta.truncation_message'));
        $this->assertEquals(5, $response->json('data.meta.returned'));
    }

    public function test_response_includes_filter_meta(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7,
            'longitude' => 46.7,
        ]);

        $response = $this->getJson('/api/map/properties');

        $response->assertJsonStructure([
            'data' => [
                'properties' => [
                    '*' => ['id', 'name', 'latitude', 'longitude', 'price', 'currency'],
                ],
                'meta' => ['total_matching', 'returned', 'is_truncated', 'limit'],
                'filter',
            ],
        ]);
    }
}
