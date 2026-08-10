<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\PublisherUpgradeRequest;
use Modules\Auth\Entities\User;
use Modules\Auth\Enums\ContactPreference;
use Modules\Auth\Enums\PublisherType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublisherControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'super-admin']);
        $permissions = [
            'offices.list', 'offices.show', 'offices.verify', 'offices.unverify',
            'offices.list-upgrade-requests', 'offices.approve-upgrade', 'offices.reject-upgrade',
        ];
        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm]);
            $adminRole->givePermissionTo($perm);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->trader = User::factory()->create();
    }

    public function test_trader_can_update_profile()
    {
        $response = $this->actingAs($this->trader)
            ->putJson('/api/publisher/profile', [
                'name' => 'Updated Name',
                'phone' => '966500000000',
                'description' => 'Real estate expert',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->trader->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_profile_requires_auth()
    {
        $response = $this->putJson('/api/publisher/profile', [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_trader_can_update_contact_preference()
    {
        $response = $this->actingAs($this->trader)
            ->putJson('/api/publisher/contact-preference', [
                'contact_preference' => 'external',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->trader->id,
            'contact_preference' => ContactPreference::EXTERNAL->value,
        ]);
    }

    public function test_trader_can_get_statistics()
    {
        $response = $this->actingAs($this->trader)
            ->getJson('/api/publisher/statistics');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [
            'total_properties', 'active_properties', 'sold_properties',
            'total_views', 'reviews_count', 'average_rating',
        ]]);
    }

    public function test_trader_can_submit_upgrade_request()
    {
        $response = $this->actingAs($this->trader)
            ->postJson('/api/publisher/upgrade-request', []);

        $response->assertStatus(200);
        $this->assertDatabaseHas('publisher_upgrade_requests', [
            'user_id' => $this->trader->id,
            'status' => 'pending',
        ]);
    }

    public function test_trader_cannot_submit_duplicate_upgrade_request()
    {
        PublisherUpgradeRequest::create([
            'user_id' => $this->trader->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->trader)
            ->postJson('/api/publisher/upgrade-request', []);

        $response->assertStatus(500);
    }

    public function test_trader_can_check_upgrade_status()
    {
        PublisherUpgradeRequest::create([
            'user_id' => $this->trader->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/publisher/upgrade-status');

        $response->assertStatus(200);
        $response->assertJsonPath('data.has_request', true);
    }

    public function test_trader_gets_no_request_when_no_upgrade()
    {
        $response = $this->actingAs($this->trader)
            ->getJson('/api/publisher/upgrade-status');

        $response->assertStatus(200);
        $response->assertJsonPath('data.has_request', false);
    }

    public function test_upgrade_request_requires_auth()
    {
        $response = $this->postJson('/api/publisher/upgrade-request', []);
        $response->assertStatus(401);
    }

    public function test_admin_can_verify_office()
    {
        $office = User::factory()->create([
            'publisher_type' => PublisherType::OFFICE->value,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/offices/{$office->id}/verify");

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $office->id,
            'is_verified' => true,
        ]);
    }

    public function test_admin_can_unverify_office()
    {
        $office = User::factory()->create([
            'publisher_type' => PublisherType::OFFICE->value,
            'is_verified' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/offices/{$office->id}/unverify");

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $office->id,
            'is_verified' => false,
        ]);
    }

    public function test_verify_requires_permission()
    {
        $trader = User::factory()->create();
        $office = User::factory()->create();

        $response = $this->actingAs($trader)
            ->postJson("/api/admin/offices/{$office->id}/verify");

        $response->assertStatus(403);
    }

    public function test_admin_can_list_upgrade_requests()
    {
        PublisherUpgradeRequest::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/office-upgrade-requests');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_approve_upgrade_request()
    {
        $request = PublisherUpgradeRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/upgrade-requests/{$request->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('publisher_upgrade_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_reject_upgrade_request()
    {
        $request = PublisherUpgradeRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/upgrade-requests/{$request->id}/reject", [
                'rejection_reason' => 'Missing documents',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('publisher_upgrade_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejection_reason' => 'Missing documents',
        ]);
    }

    public function test_cannot_approve_already_processed_request()
    {
        $request = PublisherUpgradeRequest::factory()->approved()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/upgrade-requests/{$request->id}/approve");

        $response->assertStatus(500);
    }

    public function test_verify_nonexistent_office_returns_404()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/offices/99999/verify');

        $response->assertStatus(404);
    }

    public function test_analytics_requires_subscription()
    {
        $response = $this->actingAs($this->trader)
            ->getJson('/api/publisher/analytics');

        $response->assertStatus(403);
    }

    public function test_list_offices_returns_paginated_results()
    {
        User::factory()->count(5)->create([
            'publisher_type' => PublisherType::OFFICE->value,
            'is_verified' => true,
        ]);

        $response = $this->getJson('/api/offices');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'pagination']);
    }
}
