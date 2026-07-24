<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Entities\Review;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\AdType;
use Modules\RealEstate\Enums\RentalCardStatus;
use Modules\RealEstate\Enums\ViewingStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TraderDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected User $otherTrader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $this->otherTrader = User::factory()->create();
        $this->otherTrader->assignRole('trader');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    protected function seedPermissions(): void
    {
        $perms = ['statistics.view', 'statistics.export'];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo($perms);
    }

    protected function makeProperty(User $publisher, array $attrs = []): Property
    {
        return Property::factory()->create(array_merge([
            'publisher_id' => $publisher->id,
            'status' => 'approved',
        ], $attrs));
    }

    // ============================================
    // SUMMARY
    // ============================================

    public function test_summary_returns_seven_kpis(): void
    {
        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'kpis' => [
                    '*' => ['key', 'label', 'value', 'format', 'icon'],
                ],
            ],
        ]);
        $this->assertCount(7, $response->json('data.kpis'));
    }

    public function test_summary_counts_active_properties(): void
    {
        $this->makeProperty($this->trader, ['status' => 'approved']);
        $this->makeProperty($this->trader, ['status' => 'approved']);
        $this->makeProperty($this->trader, ['status' => 'pending']);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $response->assertJsonPath('data.kpis.0.value', 2);
    }

    public function test_summary_counts_views_in_range(): void
    {
        $property = $this->makeProperty($this->trader);
        PropertyView::factory()->count(5)->create([
            'property_id' => $property->id,
            'created_at' => now()->subDays(2),
        ]);
        PropertyView::factory()->count(3)->create([
            'property_id' => $property->id,
            'created_at' => now()->subDays(40),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $viewsKpi = $kpis->firstWhere('key', 'total_views');
        $this->assertEquals(5, $viewsKpi['value']);
    }

    public function test_summary_counts_new_leads(): void
    {
        Lead::factory()->count(4)->create([
            'trader_id' => $this->trader->id,
            'created_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(4, $kpis->firstWhere('key', 'new_leads')['value']);
    }

    public function test_summary_isolated_between_traders(): void
    {
        Lead::factory()->count(3)->create(['trader_id' => $this->otherTrader->id]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(0, $kpis->firstWhere('key', 'new_leads')['value']);
    }

    public function test_summary_includes_comparison_with_previous_period(): void
    {
        Lead::factory()->count(5)->create([
            'trader_id' => $this->trader->id,
            'created_at' => now()->subDays(5),
        ]);
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'created_at' => now()->subDays(40),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $newLeadsKpi = $kpis->firstWhere('key', 'new_leads');
        $this->assertEquals(5, $newLeadsKpi['value']);
        $this->assertEquals(2, $newLeadsKpi['previous_value']);
        $this->assertEquals('up', $newLeadsKpi['change_direction']);
    }

    public function test_summary_average_rating_calculated(): void
    {
        Review::factory()->create([
            'reviewed_id' => $this->trader->id,
            'rating' => 5,
        ]);
        Review::factory()->create([
            'reviewed_id' => $this->trader->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(4, $kpis->firstWhere('key', 'average_rating')['value']);
    }

    public function test_summary_active_rentals_calculated(): void
    {
        $property = $this->makeProperty($this->trader);
        RentalCard::factory()->count(2)->create([
            'owner_id' => $this->trader->id,
            'property_id' => $property->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);
        RentalCard::factory()->create([
            'owner_id' => $this->trader->id,
            'property_id' => $property->id,
            'status' => RentalCardStatus::ENDED,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(2, $kpis->firstWhere('key', 'active_rentals')['value']);
    }

    // ============================================
    // VIEWS TREND
    // ============================================

    public function test_views_trend_returns_daily_points(): void
    {
        $property = $this->makeProperty($this->trader);
        PropertyView::factory()->create([
            'property_id' => $property->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/views-trend?period=last_7_days');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['metric', 'unit', 'points' => ['*' => ['date', 'count']]],
        ]);
        $this->assertCount(7, $response->json('data.points'));
    }

    // ============================================
    // LEADS BY STATUS
    // ============================================

    public function test_leads_by_status_distribution(): void
    {
        Lead::factory()->count(3)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::NEW,
        ]);
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::CONTACTED,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/leads-by-status?period=last_30_days');

        $response->assertStatus(200);
        $items = collect($response->json('data.items'));
        $this->assertEquals(3, $items->firstWhere('key', 'new')['count']);
        $this->assertEquals(2, $items->firstWhere('key', 'contacted')['count']);
        $this->assertEquals(5, $response->json('data.total'));
    }

    // ============================================
    // PROPERTIES BY STATUS
    // ============================================

    public function test_properties_by_status_distribution(): void
    {
        $this->makeProperty($this->trader, ['status' => 'approved']);
        $this->makeProperty($this->trader, ['status' => 'pending']);
        $this->makeProperty($this->trader, ['status' => 'approved']);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/properties-by-status?period=last_30_days');

        $response->assertStatus(200);
        $items = collect($response->json('data.items'));
        $this->assertEquals(2, $items->firstWhere('key', 'approved')['count']);
        $this->assertEquals(1, $items->firstWhere('key', 'pending')['count']);
    }

    // ============================================
    // TOP PROPERTIES
    // ============================================

    public function test_top_properties_ordered_by_views(): void
    {
        $p1 = $this->makeProperty($this->trader);
        $p2 = $this->makeProperty($this->trader);
        $p3 = $this->makeProperty($this->trader);

        PropertyView::factory()->count(10)->create(['property_id' => $p2->id, 'created_at' => now()->subDays(2)]);
        PropertyView::factory()->count(5)->create(['property_id' => $p1->id, 'created_at' => now()->subDays(2)]);
        PropertyView::factory()->count(1)->create(['property_id' => $p3->id, 'created_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/top-properties?period=last_30_days&limit=10');

        $response->assertStatus(200);
        $items = $response->json('data.items');
        $this->assertEquals($p2->id, $items[0]['id']);
        $this->assertEquals(10, $items[0]['views_in_range']);
    }

    // ============================================
    // RECENT LEADS
    // ============================================

    public function test_recent_leads_returns_latest(): void
    {
        Lead::factory()->count(3)->create(['trader_id' => $this->trader->id]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/recent-leads?limit=5');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.items'));
    }

    // ============================================
    // UPCOMING APPOINTMENTS
    // ============================================

    public function test_upcoming_appointments_only_future(): void
    {
        Appointment::factory()->create([
            'agent_id' => $this->trader->id,
            'type' => 'viewing',
            'scheduled_at' => now()->addDays(2),
            'status' => ViewingStatus::PENDING,
        ]);
        Appointment::factory()->create([
            'agent_id' => $this->trader->id,
            'type' => 'viewing',
            'scheduled_at' => now()->subDays(1),
            'status' => ViewingStatus::PENDING,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/upcoming-appointments?days=7');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.items'));
    }

    // ============================================
    // EXPIRING RENTALS
    // ============================================

    public function test_expiring_rentals_within_30_days(): void
    {
        $property = $this->makeProperty($this->trader);
        RentalCard::factory()->create([
            'owner_id' => $this->trader->id,
            'property_id' => $property->id,
            'end_date' => now()->addDays(15),
            'status' => RentalCardStatus::ACTIVE,
        ]);
        RentalCard::factory()->create([
            'owner_id' => $this->trader->id,
            'property_id' => $property->id,
            'end_date' => now()->addDays(60),
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/expiring-rentals?within_days=30');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.items'));
    }

    // ============================================
    // SPONSORED ADS
    // ============================================

    public function test_sponsored_ads_summary_calculates_spend(): void
    {
        Ad::factory()->create([
            'type' => AdType::SPONSORED,
            'user_id' => $this->trader->id,
            'status' => AdStatus::ACTIVE,
            'amount_paid' => 100,
        ]);
        Ad::factory()->create([
            'type' => AdType::SPONSORED,
            'user_id' => $this->trader->id,
            'status' => AdStatus::PAUSED,
            'amount_paid' => 50,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/sponsored-ads-summary?period=last_30_days');

        $response->assertStatus(200);
        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(1, $kpis->firstWhere('key', 'active_ads')['value']);
        $this->assertEquals(150, $kpis->firstWhere('key', 'total_spent')['value']);
    }

    // ============================================
    // CSV EXPORT
    // ============================================

    public function test_csv_export_returns_valid_csv(): void
    {
        $this->makeProperty($this->trader);
        $this->makeProperty($this->trader);

        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/trader/export/properties?period=last_30_days');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        $this->assertCount(3, $lines);
        $this->assertStringContainsString('Name', $lines[0]);
    }

    public function test_csv_export_isolated_between_traders(): void
    {
        $this->makeProperty($this->trader);
        $this->makeProperty($this->otherTrader);

        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/trader/export/properties?period=last_30_days');

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        $this->assertCount(2, $lines);
    }

    // ============================================
    // AUTHORIZATION
    // ============================================

    public function test_non_trader_user_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/dashboard/trader/summary');

        $response->assertStatus(403);
    }

    public function test_guest_user_gets_401(): void
    {
        $response = $this->getJson('/api/dashboard/trader/summary');
        $response->assertStatus(401);
    }
}
