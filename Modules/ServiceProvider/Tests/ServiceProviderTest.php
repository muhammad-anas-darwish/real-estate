<?php

namespace Modules\ServiceProvider\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'service_providers.list',
            'service_providers.show',
            'service_providers.verify',
            'service_providers.unverify',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $country = Country::factory()->create();
        $this->city = City::factory()->create(['country_id' => $country->id]);
    }

    public function test_can_register_as_service_provider()
    {
        $payload = [
            'type' => 'photographer',
            'bio' => 'Professional real estate photographer with 5 years experience.',
            'experience_years' => 5,
            'license_number' => 'PH-2026-001',
            'price_type' => 'fixed',
            'price_per_task' => 150.00,
            'coverage_city_ids' => [$this->city->id],
        ];

        $response = $this->actingAs($this->user)->postJson('/api/service-provider/register', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('service_provider_profiles', [
            'user_id' => $this->user->id,
            'type' => 'photographer',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_service_provider' => true,
            'service_provider_type' => 'photographer',
        ]);
    }

    public function test_cannot_register_twice()
    {
        $payload = [
            'type' => 'photographer',
            'coverage_city_ids' => [$this->city->id],
        ];

        $this->actingAs($this->user)->postJson('/api/service-provider/register', $payload)
            ->assertStatus(201);

        $response = $this->actingAs($this->user)->postJson('/api/service-provider/register', $payload);

        $response->assertStatus(500);
    }

    public function test_can_get_my_profile()
    {
        $this->actingAs($this->user)->postJson('/api/service-provider/register', [
            'type' => 'lawyer',
            'coverage_city_ids' => [$this->city->id],
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/service-provider/profile');

        $response->assertStatus(200);
        $response->assertJsonPath('data.type', 'lawyer');
    }

    public function test_returns_404_without_profile()
    {
        $response = $this->actingAs($this->user)->getJson('/api/service-provider/profile');

        $response->assertStatus(404);
    }

    public function test_can_update_profile()
    {
        $this->actingAs($this->user)->postJson('/api/service-provider/register', [
            'type' => 'inspector',
            'coverage_city_ids' => [$this->city->id],
        ]);

        $response = $this->actingAs($this->user)->putJson('/api/service-provider/profile', [
            'bio' => 'Updated bio.',
            'price_per_task' => 200.00,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('service_provider_profiles', [
            'user_id' => $this->user->id,
            'bio' => 'Updated bio.',
            'price_per_task' => 200.00,
        ]);
    }

    public function test_can_toggle_availability()
    {
        $this->actingAs($this->user)->postJson('/api/service-provider/register', [
            'type' => 'marketer',
            'coverage_city_ids' => [$this->city->id],
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/service-provider/availability');

        $response->assertStatus(200);
        $this->assertDatabaseHas('service_provider_profiles', [
            'user_id' => $this->user->id,
            'is_available' => false,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/service-provider/availability');

        $response->assertStatus(200);
        $this->assertDatabaseHas('service_provider_profiles', [
            'user_id' => $this->user->id,
            'is_available' => true,
        ]);
    }

    public function test_can_list_service_providers_publicly()
    {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)->postJson('/api/service-provider/register', [
            'type' => 'photographer',
            'coverage_city_ids' => [$this->city->id],
        ]);

        $profile = \Modules\ServiceProvider\Entities\ServiceProviderProfile::first();
        $profile->update(['is_verified' => true]);

        $response = $this->getJson('/api/public/service-providers');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_unverified_providers_not_listed_publicly()
    {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)->postJson('/api/service-provider/register', [
            'type' => 'photographer',
            'coverage_city_ids' => [$this->city->id],
        ]);

        $response = $this->getJson('/api/public/service-providers');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_admin_can_verify_provider()
    {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)->postJson('/api/service-provider/register', [
            'type' => 'inspector',
            'coverage_city_ids' => [$this->city->id],
        ]);

        $profile = \Modules\ServiceProvider\Entities\ServiceProviderProfile::first();

        $response = $this->actingAs($this->user)->postJson("/api/admin/service-providers/{$profile->id}/verify");

        $response->assertStatus(200);
        $this->assertDatabaseHas('service_provider_profiles', [
            'id' => $profile->id,
            'is_verified' => true,
        ]);
    }
}
