<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Entities\Review;
use Modules\RealEstate\Enums\PropertyStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TraderJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'statistics.view', 'statistics.export',
            'leads.list', 'leads.show', 'leads.create', 'leads.edit',
            'leads.change-status', 'leads.archive', 'leads.restore', 'leads.export',
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $trader = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $trader->givePermissionTo($permissions);

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_complete_trader_dashboard_journey(): void
    {
        $properties = Property::factory()->count(3)->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
        ]);

        PropertyView::factory()->count(10)->create([
            'property_id' => $properties->first()->id,
            'created_at' => now()->subDays(2),
        ]);

        Lead::factory()->count(3)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::NEW,
            'created_at' => now()->subDays(2),
        ]);
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::WON,
            'created_at' => now()->subDays(2),
        ]);

        Review::factory()->count(2)->create([
            'reviewed_id' => $this->trader->id,
            'rating' => 5,
        ]);

        // === Step 1: Trader opens summary ===
        $summary = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');
        $summary->assertStatus(200);

        $kpis = collect($summary->json('data.kpis'));
        $this->assertEquals(3, $kpis->firstWhere('key', 'active_properties')['value']);
        $this->assertEquals(10, $kpis->firstWhere('key', 'total_views')['value']);
        $this->assertEquals(5, $kpis->firstWhere('key', 'new_leads')['value']);
        $this->assertEquals(5, $kpis->firstWhere('key', 'average_rating')['value']);

        // === Step 2: Trader views views trend ===
        $trend = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/views-trend?period=last_30_days');
        $trend->assertStatus(200);
        $this->assertCount(30, $trend->json('data.points'));

        // === Step 3: Trader views leads by status ===
        $leadsByStatus = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/leads-by-status?period=last_30_days');
        $leadsByStatus->assertStatus(200);
        $this->assertEquals(5, $leadsByStatus->json('data.total'));

        // === Step 4: Trader changes period to last_7_days ===
        $shortSummary = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_7_days');
        $shortSummary->assertStatus(200);
        $this->assertEquals(5, collect($shortSummary->json('data.kpis'))
            ->firstWhere('key', 'new_leads')['value']);

        // === Step 5: Trader clicks top properties ===
        $topProps = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/top-properties?period=last_30_days&limit=5');
        $topProps->assertStatus(200);
        $this->assertEquals(3, count($topProps->json('data.items')));

        // === Step 6: Trader exports CSV ===
        $csv = $this->actingAs($this->trader)
            ->get('/api/dashboard/trader/export/properties?period=last_30_days');
        $csv->assertStatus(200);
        $csv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $lines = explode("\n", trim($csv->streamedContent()));
        $this->assertCount(4, $lines);
    }

    public function test_trader_with_no_data_sees_zero_kpis(): void
    {
        $summary = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $summary->assertStatus(200);
        $kpis = collect($summary->json('data.kpis'));
        $this->assertEquals(0, $kpis->firstWhere('key', 'active_properties')['value']);
        $this->assertEquals(0, $kpis->firstWhere('key', 'new_leads')['value']);
    }

    public function test_market_journey_for_visitor(): void
    {
        Property::factory()->count(5)->create(['status' => PropertyStatus::APPROVED]);
        Property::factory()->count(2)->create(['status' => PropertyStatus::PENDING]);

        // === Step 1: Visitor opens market overview ===
        $overview = $this->getJson('/api/market/overview?period=last_30_days');
        $overview->assertStatus(200);
        $this->assertEquals(5, collect($overview->json('data.kpis'))
            ->firstWhere('key', 'total_properties')['value']);

        // === Step 2: Visitor browses by city ===
        $byCity = $this->getJson('/api/market/by-city?period=last_30_days');
        $byCity->assertStatus(200);
        $this->assertEquals(5, $byCity->json('data.total'));

        // === Step 3: Visitor checks price ranges ===
        $byPrice = $this->getJson('/api/market/by-price-range?period=last_30_days');
        $byPrice->assertStatus(200);

        // === Step 4: Visitor views listings trend ===
        $trend = $this->getJson('/api/market/listings-trend?period=last_7_days');
        $trend->assertStatus(200);
        $this->assertCount(7, $trend->json('data.points'));
    }

    public function test_property_stats_journey(): void
    {
        $property = Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
        ]);

        PropertyView::factory()->count(20)->create([
            'property_id' => $property->id,
            'created_at' => now()->subDays(2),
        ]);

        Review::factory()->count(3)->create([
            'property_id' => $property->id,
            'rating' => 4,
        ]);

        $stats = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $stats->assertStatus(200);
        $this->assertEquals(20, collect($stats->json('data.kpis'))
            ->firstWhere('key', 'total_views')['value']);
        $this->assertEquals(4, collect($stats->json('data.kpis'))
            ->firstWhere('key', 'average_rating')['value']);

        $this->assertCount(4, $stats->json('data.funnel'));
        $this->assertGreaterThan(0, count($stats->json('data.distributions.traffic_sources.items')));
    }
}
