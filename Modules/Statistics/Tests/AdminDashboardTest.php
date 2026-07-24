<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\PublisherUpgradeRequest;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\AdType;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionPlan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'admin_statistics.view', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    // ============================================
    // OVERVIEW
    // ============================================

    public function test_overview_returns_kpis(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/overview?period=last_30_days');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(5, count($response->json('data.kpis')));
    }

    public function test_overview_counts_new_users(): void
    {
        User::factory()->count(5)->create(['created_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/overview?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        // 5 new + 1 admin (created in setUp) = 6
        $this->assertEquals(6, $kpis->firstWhere('key', 'total_users')['value']);
    }

    public function test_overview_includes_registration_trend(): void
    {
        User::factory()->create(['created_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/overview?period=last_7_days');

        $charts = $response->json('data.charts');
        $this->assertGreaterThanOrEqual(1, count($charts));
        $this->assertEquals('user_registrations', $charts[0]['metric']);
    }

    // ============================================
    // PROPERTIES
    // ============================================

    public function test_properties_status_distribution(): void
    {
        Property::factory()->count(3)->create(['status' => PropertyStatus::APPROVED]);
        Property::factory()->count(2)->create(['status' => PropertyStatus::PENDING]);
        Property::factory()->create(['status' => PropertyStatus::REJECTED, 'rejection_reason' => 'صور سيئة']);
        Property::factory()->create(['status' => PropertyStatus::REJECTED, 'rejection_reason' => 'صور سيئة']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/properties?period=last_30_days');

        $response->assertStatus(200);
        $items = collect($response->json('data.distributions.status.items'));
        $this->assertEquals(3, $items->firstWhere('key', 'approved')['count']);
        $this->assertEquals(2, $items->firstWhere('key', 'pending')['count']);
    }

    public function test_properties_top_rejection_reasons(): void
    {
        Property::factory()->count(3)->create(['status' => PropertyStatus::REJECTED, 'rejection_reason' => 'صور سيئة']);
        Property::factory()->count(2)->create(['status' => PropertyStatus::REJECTED, 'rejection_reason' => 'سعر غير واقعي']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/properties?period=last_30_days');

        $reasons = $response->json('data.lists.rejection_reasons.items');
        $this->assertEquals('صور سيئة', $reasons[0]['reason']);
        $this->assertEquals(3, $reasons[0]['count']);
    }

    // ============================================
    // CRM
    // ============================================

    public function test_crm_leads_distribution(): void
    {
        Lead::factory()->count(3)->create(['status' => LeadStatus::NEW]);
        Lead::factory()->count(2)->create(['status' => LeadStatus::WON]);
        Lead::factory()->create(['status' => LeadStatus::LOST]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/crm?period=last_30_days');

        $response->assertStatus(200);
        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(6, $kpis->firstWhere('key', 'total_leads')['value']);
        $this->assertGreaterThan(0, $kpis->firstWhere('key', 'conversion_rate')['value']);
    }

    public function test_crm_top_lead_sources(): void
    {
        Lead::factory()->count(5)->create(['source' => LeadSource::WEBSITE]);
        Lead::factory()->count(3)->create(['source' => LeadSource::WHATSAPP]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/crm?period=last_30_days');

        $sources = $response->json('data.lists.top_sources.items');
        $this->assertEquals('website', $sources[0]['source']);
        $this->assertEquals(5, $sources[0]['count']);
    }

    // ============================================
    // ADS
    // ============================================

    public function test_ads_calculates_revenue(): void
    {
        Ad::factory()->create([
            'type' => AdType::SPONSORED,
            'status' => AdStatus::ACTIVE,
            'amount_paid' => 100,
        ]);
        Ad::factory()->create([
            'type' => AdType::SPONSORED,
            'status' => AdStatus::ACTIVE,
            'amount_paid' => 200,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/ads?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(2, $kpis->firstWhere('key', 'active_sponsored')['value']);
        $this->assertEquals(300, $kpis->firstWhere('key', 'total_ad_revenue')['value']);
    }

    // ============================================
    // SUBSCRIPTIONS
    // ============================================

    public function test_subscriptions_mrr_calculation(): void
    {
        $plan = SubscriptionPlan::factory()->create(['price' => 100, 'is_active' => true]);
        Subscription::factory()->count(3)->create([
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/subscriptions?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(300, $kpis->firstWhere('key', 'mrr')['value']);
        $this->assertEquals(3, $kpis->firstWhere('key', 'active_subs')['value']);
    }

    public function test_subscriptions_mrr_trend_chart(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/subscriptions?period=last_30_days');

        $charts = $response->json('data.charts');
        $mrrChart = collect($charts)->firstWhere('metric', 'mrr');
        $this->assertNotNull($mrrChart);
        $this->assertEquals(12, count($mrrChart['points']));
    }

    // ============================================
    // MODERATION
    // ============================================

    public function test_moderation_counts_pending_items(): void
    {
        Property::factory()->count(3)->create(['status' => PropertyStatus::PENDING]);
        PublisherUpgradeRequest::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/statistics/moderation');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(3, $kpis->firstWhere('key', 'pending_properties')['value']);
        $this->assertEquals(2, $kpis->firstWhere('key', 'pending_verifications')['value']);
    }

    public function test_moderation_returns_oldest_pending(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::PENDING,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/statistics/moderation');

        $this->assertNotNull($response->json('data.oldest.pending_property_created_at'));
    }

    // ============================================
    // COMMUNICATION
    // ============================================

    public function test_communication_kpis(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/communication?period=last_30_days');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($response->json('data.kpis')));
    }

    // ============================================
    // AUTHORIZATION
    // ============================================

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

    public function test_super_admin_can_access_all_tabs(): void
    {
        $endpoints = [
            'overview', 'properties', 'crm', 'ads', 'subscriptions', 'moderation', 'communication',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($this->admin)
                ->getJson("/api/admin/statistics/{$endpoint}?period=last_30_days");

            $response->assertStatus(200, "Failed for endpoint: {$endpoint}");
        }
    }
}
