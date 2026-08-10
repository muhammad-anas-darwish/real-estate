<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Tests\TestCase;

class MarketStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_overview_publicly_accessible(): void
    {
        $response = $this->getJson('/api/market/overview?period=last_30_days');
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data.kpis')));
    }

    public function test_overview_counts_approved_properties(): void
    {
        Property::factory()->count(3)->create(['status' => PropertyStatus::APPROVED]);
        Property::factory()->create(['status' => PropertyStatus::PENDING]);

        $response = $this->getJson('/api/market/overview?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(3, $kpis->firstWhere('key', 'total_properties')['value']);
    }

    public function test_by_city_distribution(): void
    {
        $city = City::first();
        Property::factory()->count(2)->create([
            'status' => PropertyStatus::APPROVED,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson('/api/market/by-city?period=last_30_days');
        $response->assertStatus(200);

        $items = $response->json('data.items');
        $cityItem = collect($items)->firstWhere('city_id', $city->id);
        $this->assertNotNull($cityItem);
        $this->assertEquals(2, $cityItem['count']);
    }

    public function test_by_price_range_distribution(): void
    {
        Property::factory()->create(['status' => PropertyStatus::APPROVED, 'price' => 50000]);
        Property::factory()->create(['status' => PropertyStatus::APPROVED, 'price' => 200000]);
        Property::factory()->create(['status' => PropertyStatus::APPROVED, 'price' => 2000000]);

        $response = $this->getJson('/api/market/by-price-range?period=last_30_days');
        $response->assertStatus(200);

        $items = collect($response->json('data.items'));
        $this->assertEquals(1, $items->firstWhere('key', '0-100k')['count']);
        $this->assertEquals(1, $items->firstWhere('key', '100k-300k')['count']);
        $this->assertEquals(1, $items->firstWhere('key', '1m+')['count']);
    }

    public function test_top_viewed_orders_by_views(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'views' => 100,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'views' => 500,
        ]);

        $response = $this->getJson('/api/market/top-viewed?period=last_30_days&limit=10');
        $response->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertEquals(500, $items[0]['views']);
    }

    public function test_listings_trend_returns_daily_points(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->getJson('/api/market/listings-trend?period=last_7_days');
        $response->assertStatus(200);
        $this->assertCount(7, $response->json('data.points'));
    }

    public function test_market_excludes_pending_properties(): void
    {
        Property::factory()->create(['status' => PropertyStatus::PENDING]);
        Property::factory()->create(['status' => PropertyStatus::REJECTED]);

        $response = $this->getJson('/api/market/overview?period=last_30_days');
        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(0, $kpis->firstWhere('key', 'total_properties')['value']);
    }

    public function test_by_category_returns_distribution(): void
    {
        Property::factory()->count(2)->create(['status' => PropertyStatus::APPROVED]);
        Property::factory()->count(3)->create(['status' => PropertyStatus::APPROVED]);

        $response = $this->getJson('/api/market/by-category?period=last_30_days');
        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.total'));
    }

    public function test_top_saved_orders_by_favorites(): void
    {
        $p1 = Property::factory()->create(['status' => PropertyStatus::APPROVED]);
        $p2 = Property::factory()->create(['status' => PropertyStatus::APPROVED]);

        $p1->favoritedBy()->attach(User::factory()->create());
        $p1->favoritedBy()->attach(User::factory()->create());
        $p2->favoritedBy()->attach(User::factory()->create());

        $response = $this->getJson('/api/market/top-saved?period=last_30_days&limit=10');
        $response->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertEquals($p1->id, $items[0]['id']);
        $this->assertEquals(2, $items[0]['favorites_count']);
    }
}
