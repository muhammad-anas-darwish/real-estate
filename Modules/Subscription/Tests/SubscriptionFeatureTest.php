<?php

namespace Modules\Subscription\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Subscription\Entities\SubscriptionFeature;
use Modules\Subscription\Enums\FeatureType;
use Modules\Subscription\Services\FeatureService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'subscription_features.list',
            'subscription_features.show',
            'subscription_features.create',
            'subscription_features.edit',
            'subscription_features.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_can_list_features()
    {
        SubscriptionFeature::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/admin/subscription/features');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_feature()
    {
        $payload = [
            'name' => 'Virtual Tours',
            'slug' => 'virtual-tours',
            'type' => FeatureType::TOGGLE->value,
            'description' => 'Enable virtual tour support',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/subscription/features', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subscription_features', ['slug' => 'virtual-tours']);
    }

    public function test_can_show_feature()
    {
        $feature = SubscriptionFeature::factory()->create();
        $found = SubscriptionFeature::find($feature->id);

        $this->assertNotNull($found);
        $this->assertEquals($feature->id, $found->id);
    }

    public function test_can_update_feature()
    {
        $feature = SubscriptionFeature::factory()->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/admin/subscription/features/{$feature->id}", [
                'name' => 'Updated Feature',
                'type' => FeatureType::LIMIT->value,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscription_features', [
            'id' => $feature->id,
            'name' => 'Updated Feature',
            'type' => FeatureType::LIMIT->value,
        ]);
    }

    public function test_can_delete_feature()
    {
        $feature = SubscriptionFeature::factory()->create();
        $feature->fresh();
        $this->assertDatabaseHas('subscription_features', ['id' => $feature->id]);

        $feature->delete();
        $this->assertDatabaseMissing('subscription_features', ['id' => $feature->id]);
    }
}
