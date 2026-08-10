<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Entities\User;
use Modules\Statistics\Enums\StatsPeriod;
use Modules\Statistics\ValueObjects\DateRange;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\PermissionSeeder']);
    }

    public function test_statistics_permissions_are_seeded(): void
    {
        $this->assertTrue(Permission::where('name', 'statistics.view')->exists());
        $this->assertTrue(Permission::where('name', 'statistics.export')->exists());
        $this->assertTrue(Permission::where('name', 'admin_statistics.view')->exists());
    }

    public function test_trader_role_has_statistics_view_and_export(): void
    {
        $trader = Role::where('name', 'trader')->first();
        $this->assertNotNull($trader);
        $this->assertTrue($trader->hasPermissionTo('statistics.view'));
        $this->assertTrue($trader->hasPermissionTo('statistics.export'));
    }

    public function test_stats_period_enum_supports_comparison(): void
    {
        $this->assertTrue(StatsPeriod::LAST_30_DAYS->supportsComparison());
        $this->assertTrue(StatsPeriod::THIS_MONTH->supportsComparison());
        $this->assertFalse(StatsPeriod::TODAY->supportsComparison());
        $this->assertFalse(StatsPeriod::CUSTOM->supportsComparison());
    }

    public function test_stats_period_enum_labels(): void
    {
        $this->assertEquals('اليوم', StatsPeriod::TODAY->label());
        $this->assertEquals('آخر 30 يوم', StatsPeriod::LAST_30_DAYS->label());
    }

    public function test_date_range_from_last_30_days(): void
    {
        $range = DateRange::fromPeriod(StatsPeriod::LAST_30_DAYS);
        $this->assertEquals(30, $range->days());
        $this->assertNotNull($range->previousRange());
    }

    public function test_date_range_from_today(): void
    {
        $range = DateRange::fromPeriod(StatsPeriod::TODAY);
        $this->assertEquals(1, $range->days());
        // previousRange() always returns a range, but supportsComparison controls
        // whether the dashboard uses it for comparison
        $this->assertFalse(StatsPeriod::TODAY->supportsComparison());
    }

    public function test_date_range_from_custom(): void
    {
        $range = DateRange::custom('2026-01-01', '2026-01-31');
        $this->assertEquals(31, $range->days());
    }

    public function test_date_range_previous_range_uses_same_duration(): void
    {
        $range = DateRange::fromPeriod(StatsPeriod::LAST_7_DAYS);
        $previous = $range->previousRange();

        $this->assertEquals($range->days(), $previous->days());
        $this->assertLessThan($range->from, $previous->from);
    }

    public function test_trader_summary_endpoint_returns_200(): void
    {
        $trader = User::factory()->create();
        $trader->assignRole('trader');

        $response = $this->actingAs($trader)
            ->getJson('/api/dashboard/trader/summary');

        $response->assertStatus(200);
    }

    public function test_market_overview_is_public(): void
    {
        $response = $this->getJson('/api/market/overview');

        $this->assertContains($response->getStatusCode(), [200, 501]);
    }

    public function test_admin_endpoint_requires_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/statistics/overview');

        $response->assertStatus(403);
    }

    public function test_admin_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/admin/statistics/overview');

        $response->assertStatus(401);
    }

    public function test_trader_routes_require_trader_role(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/dashboard/trader/summary');

        $response->assertStatus(403);
    }

    public function test_all_statistics_routes_registered(): void
    {
        $expectedPaths = [
            'api/dashboard/trader/summary',
            'api/dashboard/trader/views-trend',
            'api/dashboard/trader/leads-by-status',
            'api/dashboard/trader/properties-by-status',
            'api/dashboard/trader/top-properties',
            'api/dashboard/trader/recent-leads',
            'api/dashboard/trader/upcoming-appointments',
            'api/dashboard/trader/expiring-rentals',
            'api/dashboard/trader/sponsored-ads-summary',
            'api/dashboard/trader/export/properties',
            'api/dashboard/properties/{property}/stats',
            'api/market/overview',
            'api/market/by-city',
            'api/market/by-category',
            'api/market/by-price-range',
            'api/market/top-viewed',
            'api/market/top-saved',
            'api/market/listings-trend',
            'api/admin/statistics/overview',
            'api/admin/statistics/properties',
            'api/admin/statistics/crm',
            'api/admin/statistics/ads',
            'api/admin/statistics/subscriptions',
            'api/admin/statistics/moderation',
            'api/admin/statistics/communication',
        ];

        $registeredPaths = collect(Route::getRoutes())
            ->map(fn ($r) => $r->uri())
            ->toArray();

        foreach ($expectedPaths as $path) {
            $this->assertContains($path, $registeredPaths, "Route {$path} not registered");
        }
    }
}
