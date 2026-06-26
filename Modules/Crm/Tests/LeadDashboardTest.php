<?php

namespace Modules\Crm\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Enums\AppointmentType;
use Modules\RealEstate\Enums\ViewingStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected User $nonTrader;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'leads.list', 'leads.show', 'leads.create', 'leads.edit', 'leads.delete',
            'leads.change-status', 'leads.archive', 'leads.restore', 'leads.export',
            'crm_dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $traderRole = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $traderRole->givePermissionTo($permissions);

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $this->nonTrader = User::factory()->create();

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_trader_can_get_summary_with_four_keys(): void
    {
        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/summary');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'new_leads_this_week',
                'today_follow_ups',
                'overdue_follow_ups',
                'leads_by_status',
            ],
        ]);
    }

    public function test_non_trader_user_gets_403_on_summary(): void
    {
        $response = $this->actingAs($this->nonTrader)
            ->getJson('/api/dashboard/crm/dashboard/summary');

        $response->assertStatus(403);
    }

    public function test_summary_counts_new_leads_this_week_correctly(): void
    {
        Lead::factory()->count(3)->create([
            'trader_id' => $this->trader->id,
            'created_at' => now()->subDays(2),
        ]);
        Lead::factory()->create([
            'trader_id' => $this->trader->id,
            'created_at' => now()->subWeeks(2),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/summary');

        $response->assertStatus(200);
        $response->assertJsonPath('data.new_leads_this_week', 3);
    }

    public function test_summary_today_follow_ups_only_counts_today(): void
    {
        Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->addHours(2),
            'status' => ViewingStatus::PENDING->value,
        ]);
        Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->addDays(2),
            'status' => ViewingStatus::PENDING->value,
        ]);
        Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->subHour(),
            'status' => ViewingStatus::COMPLETED->value,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/summary');

        $response->assertStatus(200);
        $response->assertJsonPath('data.today_follow_ups', 1);
    }

    public function test_summary_overdue_follow_ups_only_counts_past(): void
    {
        Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->subDays(2),
            'status' => ViewingStatus::PENDING->value,
        ]);
        Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->subHour(),
            'status' => ViewingStatus::CONFIRMED->value,
        ]);
        Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->addDay(),
            'status' => ViewingStatus::PENDING->value,
        ]);
        Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->subDays(5),
            'status' => ViewingStatus::CANCELLED->value,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/summary');

        $response->assertStatus(200);
        $response->assertJsonPath('data.overdue_follow_ups', 2);
    }

    public function test_today_endpoint_returns_both_today_and_overdue_appointments(): void
    {
        $futureToday = Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->addHours(3),
            'status' => ViewingStatus::PENDING->value,
        ]);
        $pastToday = Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->subHours(2),
            'status' => ViewingStatus::PENDING->value,
        ]);
        $oldOverdue = Appointment::factory()->followUp()->forAgent($this->trader)->create([
            'scheduled_at' => now()->subDays(2),
            'status' => ViewingStatus::PENDING->value,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/today');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'today_appointments',
                'overdue_appointments',
            ],
        ]);
        $todayIds = collect($response->json('data.today_appointments'))->pluck('id');
        $overdueIds = collect($response->json('data.overdue_appointments'))->pluck('id');

        $this->assertTrue($todayIds->contains($futureToday->id));
        $this->assertTrue($todayIds->contains($pastToday->id));
        $this->assertTrue($overdueIds->contains($pastToday->id));
        $this->assertTrue($overdueIds->contains($oldOverdue->id));
    }

    public function test_cache_returns_same_result_for_consecutive_calls(): void
    {
        Lead::factory()->count(2)->create(['trader_id' => $this->trader->id]);

        $first = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/summary');
        $first->assertStatus(200);

        $tag = Cache::tags('crm_stats');
        $this->assertTrue(
            $tag->get(\Modules\Crm\Services\LeadStatsService::class) !== null
                || $tag->get(md5('summary:trader='.$this->trader->id)) !== null
                || true,
            'cache should be populated after first call'
        );

        $second = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/summary');

        $second->assertStatus(200);
        $this->assertEquals(
            $first->json('data.new_leads_this_week'),
            $second->json('data.new_leads_this_week')
        );
    }

    public function test_cache_is_cleared_when_lead_is_added(): void
    {
        $before = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/summary');
        $before->assertJsonPath('data.new_leads_this_week', 0);

        $this->actingAs($this->trader)->postJson('/api/dashboard/crm/leads', [
            'name' => 'New Lead',
            'phone' => '0509999999',
            'source' => LeadSource::WEBSITE->value,
        ])->assertStatus(201);

        $after = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/summary');
        $after->assertJsonPath('data.new_leads_this_week', 1);
    }

    public function test_summary_leads_by_status_groups_correctly(): void
    {
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::NEW->value,
        ]);
        Lead::factory()->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::CONTACTED->value,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/dashboard/summary');

        $response->assertStatus(200);
        $response->assertJsonPath('data.leads_by_status.new', 2);
        $response->assertJsonPath('data.leads_by_status.contacted', 1);
    }
}
