<?php

namespace Modules\ServiceProvider\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\ServiceProvider\Entities\ServiceProviderProfile;
use Modules\ServiceProvider\Enums\ServiceRequestStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;

    protected User $providerUser;

    protected ServiceProviderProfile $providerProfile;

    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        foreach (['service_requests.list', 'service_requests.show'] as $perm) {
            Permission::create(['name' => $perm]);
            $role->givePermissionTo($perm);
        }

        $this->client = User::factory()->create();
        $this->providerUser = User::factory()->create();
        $this->providerUser->assignRole($role);

        $country = Country::factory()->create();
        $this->city = City::factory()->create(['country_id' => $country->id]);

        $this->actingAs($this->providerUser)->postJson('/api/service-provider/register', [
            'type' => 'photographer',
            'coverage_city_ids' => [$this->city->id],
        ]);

        $this->providerProfile = ServiceProviderProfile::first();
        $this->providerProfile->update(['is_verified' => true]);
    }

    public function test_client_can_create_service_request()
    {
        $payload = [
            'service_type' => 'photography',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'client_notes' => 'Need photos of my apartment.',
        ];

        $response = $this->actingAs($this->client)->postJson('/api/service-requests', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.service_type', 'photography');
        $response->assertJsonPath('data.status', ServiceRequestStatus::PENDING->value);
        $this->assertDatabaseHas('service_requests', [
            'client_id' => $this->client->id,
            'service_type' => 'photography',
        ]);
    }

    public function test_client_can_list_own_requests()
    {
        $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'legal',
        ]);

        $response = $this->actingAs($this->client)->getJson('/api/service-requests');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_client_can_view_own_request()
    {
        $req = $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'marketing',
        ]);

        $id = $req->json('data.id');

        $response = $this->actingAs($this->client)->getJson("/api/service-requests/{$id}");

        $response->assertStatus(200);
    }

    public function test_client_cannot_view_others_request()
    {
        $req = $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'photography',
        ]);

        $otherUser = User::factory()->create();
        $id = $req->json('data.id');

        $response = $this->actingAs($otherUser)->getJson("/api/service-requests/{$id}");

        $response->assertStatus(403);
    }

    public function test_create_request_with_specific_provider()
    {
        $payload = [
            'service_type' => 'inspection',
            'provider_id' => $this->providerProfile->id,
            'price' => 200.00,
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
        ];

        $response = $this->actingAs($this->client)->postJson('/api/service-requests', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.provider_id', $this->providerProfile->id);
    }

    public function test_provider_can_accept_request()
    {
        $req = $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'photography',
            'provider_id' => $this->providerProfile->id,
        ]);

        $id = $req->json('data.id');

        $response = $this->actingAs($this->providerUser)->postJson("/api/service-provider/service-requests/{$id}/accept");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ServiceRequestStatus::ACCEPTED->value);
    }

    public function test_provider_can_reject_request()
    {
        $req = $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'photography',
        ]);

        $id = $req->json('data.id');

        $response = $this->actingAs($this->providerUser)->postJson("/api/service-provider/service-requests/{$id}/reject");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ServiceRequestStatus::REJECTED->value);
    }

    public function test_provider_can_start_and_complete()
    {
        $req = $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'photography',
            'provider_id' => $this->providerProfile->id,
        ]);

        $id = $req->json('data.id');

        $this->actingAs($this->providerUser)->postJson("/api/service-provider/service-requests/{$id}/accept");

        $this->actingAs($this->providerUser)->postJson("/api/service-provider/service-requests/{$id}/start");

        $response = $this->actingAs($this->providerUser)
            ->postJson("/api/service-provider/service-requests/{$id}/complete", [
                'provider_notes' => 'Photos uploaded successfully.',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ServiceRequestStatus::COMPLETED->value);
    }

    public function test_client_can_cancel_pending_request()
    {
        $req = $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'legal',
        ]);

        $id = $req->json('data.id');

        $response = $this->actingAs($this->client)->postJson("/api/service-requests/{$id}/cancel");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ServiceRequestStatus::CANCELLED->value);
    }

    public function test_cannot_accept_non_pending_request()
    {
        $req = $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'photography',
            'provider_id' => $this->providerProfile->id,
        ]);

        $id = $req->json('data.id');

        $this->actingAs($this->providerUser)->postJson("/api/service-provider/service-requests/{$id}/accept");

        $response = $this->actingAs($this->providerUser)->postJson("/api/service-provider/service-requests/{$id}/reject");

        $response->assertStatus(500);
    }

    public function test_provider_gets_available_requests()
    {
        $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'photography',
        ]);

        $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'inspection',
        ]);

        $response = $this->actingAs($this->providerUser)
            ->getJson('/api/service-provider/service-requests');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_non_provider_cannot_access_provider_routes()
    {
        $response = $this->actingAs($this->client)->getJson('/api/service-provider/service-requests');

        $response->assertStatus(403);
    }
}
