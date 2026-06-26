<?php

namespace Modules\Crm\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Enums\AppointmentType;
use Modules\RealEstate\Enums\ViewingStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected User $otherTrader;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'leads.list', 'leads.show', 'leads.create', 'leads.edit', 'leads.delete',
            'leads.change-status', 'leads.archive', 'leads.restore', 'leads.export',
            'appointments.list', 'appointments.show', 'appointments.create', 'appointments.edit', 'appointments.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $traderRole = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $traderRole->givePermissionTo($permissions);

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $this->otherTrader = User::factory()->create();
        $this->otherTrader->assignRole('trader');
    }

    public function test_trader_can_create_lead_with_valid_data(): void
    {
        $payload = [
            'name' => 'Ahmed',
            'phone' => '0501234567',
            'email' => 'ahmed@example.com',
            'source' => LeadSource::WHATSAPP->value,
        ];

        $response = $this->actingAs($this->trader)->postJson('/api/dashboard/crm/leads', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', LeadStatus::NEW->value);
        $response->assertJsonPath('data.source', LeadSource::WHATSAPP->value);
        $this->assertDatabaseHas('leads', [
            'name' => 'Ahmed',
            'phone' => '0501234567',
            'trader_id' => $this->trader->id,
        ]);
    }

    public function test_trader_can_create_lead_with_duplicate_phone_but_endpoint_reports_it(): void
    {
        Lead::create([
            'trader_id' => $this->trader->id,
            'name' => 'First',
            'phone' => '0501111111',
            'source' => LeadSource::WEBSITE->value,
            'status' => LeadStatus::NEW->value,
            'status_changed_at' => now(),
            'last_activity_at' => now(),
        ]);

        $payload = [
            'name' => 'Second',
            'phone' => '0501111111',
            'source' => LeadSource::WHATSAPP->value,
        ];

        $response = $this->actingAs($this->trader)->postJson('/api/dashboard/crm/leads', $payload);
        $response->assertStatus(201);

        $checkResponse = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/leads/check-duplicate?phone=0501111111');
        $checkResponse->assertStatus(200);
        $checkResponse->assertJsonPath('data.duplicate', true);
    }

    public function test_create_lead_with_missing_fields_returns_422(): void
    {
        $response = $this->actingAs($this->trader)->postJson('/api/dashboard/crm/leads', [
            'email' => 'invalid@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'phone', 'source']);
    }

    public function test_can_list_leads_with_pagination_search_and_status_filter(): void
    {
        Lead::factory()->count(3)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::NEW->value,
            'name' => 'Ahmed',
        ]);
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::CONTACTED->value,
            'name' => 'Sara',
        ]);

        $listResponse = $this->actingAs($this->trader)->getJson('/api/dashboard/crm/leads');
        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(5, 'data');

        $searchResponse = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/leads?search=Ahmed');
        $searchResponse->assertStatus(200);
        $searchResponse->assertJsonCount(3, 'data');

        $filterResponse = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/leads?status=contacted');
        $filterResponse->assertStatus(200);
        $filterResponse->assertJsonCount(2, 'data');
    }

    public function test_change_status_to_non_lost_without_lost_reason_succeeds(): void
    {
        $lead = Lead::factory()->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::NEW->value,
        ]);

        $response = $this->actingAs($this->trader)
            ->patchJson("/api/dashboard/crm/leads/{$lead->id}/status", [
                'status' => LeadStatus::CONTACTED->value,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => LeadStatus::CONTACTED->value,
        ]);
    }

    public function test_change_status_to_lost_without_lost_reason_returns_422(): void
    {
        $lead = Lead::factory()->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::QUALIFIED->value,
        ]);

        $response = $this->actingAs($this->trader)
            ->patchJson("/api/dashboard/crm/leads/{$lead->id}/status", [
                'status' => LeadStatus::LOST->value,
            ]);

        $response->assertStatus(422);
    }

    public function test_archive_moves_lead_to_archived_scope(): void
    {
        $lead = Lead::factory()->create([
            'trader_id' => $this->trader->id,
            'archived_at' => null,
        ]);

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'archived_at' => null]);

        $response = $this->actingAs($this->trader)
            ->postJson("/api/dashboard/crm/leads/{$lead->id}/archive");
        $response->assertStatus(200);

        $this->assertNotNull(Lead::find($lead->id)->archived_at);

        $listResponse = $this->actingAs($this->trader)->getJson('/api/dashboard/crm/leads');
        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(0, 'data');

        $archivedResponse = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/crm/leads/archived');
        $archivedResponse->assertStatus(200);
        $archivedResponse->assertJsonCount(1, 'data');
    }

    public function test_restore_within_30_days_succeeds(): void
    {
        $lead = Lead::factory()->create([
            'trader_id' => $this->trader->id,
            'archived_at' => now()->subDays(10),
        ]);
        $lead->delete();

        $response = $this->actingAs($this->trader)
            ->postJson("/api/dashboard/crm/leads/{$lead->id}/restore");

        $response->assertStatus(200);
        $this->assertNull(Lead::withTrashed()->find($lead->id)->archived_at);
    }

    public function test_restore_after_30_days_returns_422(): void
    {
        $lead = Lead::factory()->create([
            'trader_id' => $this->trader->id,
            'archived_at' => now()->subDays(40),
        ]);
        $lead->delete();

        $response = $this->actingAs($this->trader)
            ->postJson("/api/dashboard/crm/leads/{$lead->id}/restore");

        $response->assertStatus(422);
    }

    public function test_other_trader_cannot_access_lead(): void
    {
        $lead = Lead::factory()->create([
            'trader_id' => $this->trader->id,
        ]);

        $showResponse = $this->actingAs($this->otherTrader)
            ->getJson("/api/dashboard/crm/leads/{$lead->id}");
        $showResponse->assertStatus(403);

        $listResponse = $this->actingAs($this->otherTrader)
            ->getJson('/api/dashboard/crm/leads');
        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(0, 'data');
    }

    public function test_last_activity_at_updates_on_any_modification(): void
    {
        $createdAt = now()->subDays(2);
        $lead = Lead::factory()->create([
            'trader_id' => $this->trader->id,
            'last_activity_at' => $createdAt,
        ]);

        Carbon::setTestNow(now()->addHour());

        $this->actingAs($this->trader)
            ->patchJson("/api/dashboard/crm/leads/{$lead->id}", [
                'name' => 'Updated Name',
            ])
            ->assertStatus(200);

        $this->assertNotEquals(
            $createdAt->toDateTimeString(),
            Lead::find($lead->id)->last_activity_at->toDateTimeString()
        );
    }
}
