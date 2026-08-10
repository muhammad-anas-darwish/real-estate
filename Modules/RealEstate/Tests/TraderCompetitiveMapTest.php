<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TraderCompetitiveMapTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'properties.list', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo('properties.list');

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_returns_my_and_competitor_properties_separately(): void
    {
        Property::factory()->count(3)->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        Property::factory()->count(5)->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/competitive-map');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.my_properties'));
        $this->assertCount(5, $response->json('data.competitor_properties'));
        $this->assertEquals(3, $response->json('data.meta.my_count'));
        $this->assertEquals(5, $response->json('data.meta.competitor_count'));
    }

    public function test_excludes_own_pending_properties(): void
    {
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::PENDING,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/competitive-map');

        $this->assertCount(1, $response->json('data.my_properties'));
    }

    public function test_excludes_properties_without_coordinates(): void
    {
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => null, 'longitude' => null,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/competitive-map');

        $this->assertCount(0, $response->json('data.my_properties'));
    }

    public function test_filter_by_city(): void
    {
        $city = City::first();
        $otherCity = City::factory()->create(['country_id' => $city->country_id]);

        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'city_id' => $city->id,
        ]);
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'city_id' => $otherCity->id,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/trader/competitive-map?city_id={$city->id}");

        $this->assertCount(1, $response->json('data.my_properties'));
    }

    public function test_requires_trader_role(): void
    {
        $response = $this->getJson('/api/dashboard/trader/competitive-map');
        $response->assertStatus(401);
    }
}
