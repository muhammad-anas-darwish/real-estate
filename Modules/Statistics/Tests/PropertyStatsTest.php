<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Enums\PropertyStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyStatsTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'statistics.view', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo('statistics.view');

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_show_returns_seven_kpis(): void
    {
        $property = Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $response->assertStatus(200);
        $this->assertCount(7, $response->json('data.kpis'));
    }

    public function test_show_counts_views_in_range(): void
    {
        $property = Property::factory()->create(['publisher_id' => $this->trader->id]);
        PropertyView::factory()->count(5)->create([
            'property_id' => $property->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(5, $kpis->firstWhere('key', 'total_views')['value']);
    }

    public function test_show_calculates_funnel(): void
    {
        $property = Property::factory()->create(['publisher_id' => $this->trader->id]);
        PropertyView::factory()->count(10)->create(['property_id' => $property->id]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $funnel = $response->json('data.funnel');
        $this->assertEquals('view', $funnel[0]['stage']);
        $this->assertEquals(10, $funnel[0]['count']);
    }

    public function test_show_calculates_days_on_market(): void
    {
        $property = Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'created_at' => now()->subDays(15),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $this->assertEquals(15, $response->json('data.meta.days_on_market'));
    }

    public function test_show_returns_traffic_sources(): void
    {
        $property = Property::factory()->create(['publisher_id' => $this->trader->id]);
        PropertyView::factory()->count(3)->create([
            'property_id' => $property->id,
            'user_id' => null,
        ]);
        PropertyView::factory()->count(2)->create([
            'property_id' => $property->id,
            'user_id' => User::factory(),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $sources = $response->json('data.distributions.traffic_sources');
        $this->assertEquals('traffic_source', $sources['dimension']);
        $this->assertEquals(3, collect($sources['items'])->firstWhere('key', 'direct')['count']);
        $this->assertEquals(2, collect($sources['items'])->firstWhere('key', 'registered')['count']);
    }

    public function test_show_404_for_missing_property(): void
    {
        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/properties/99999/stats?period=last_30_days');

        $response->assertStatus(404);
    }

    public function test_show_requires_auth(): void
    {
        $property = Property::factory()->create(['publisher_id' => $this->trader->id]);

        $response = $this->getJson("/api/dashboard/properties/{$property->id}/stats");
        $response->assertStatus(401);
    }
}
