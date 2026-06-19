<?php

namespace Modules\Subscription\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Subscription\Entities\SubscriptionFeature;
use Modules\Subscription\Entities\SubscriptionPlan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'subscription_plans.list',
            'subscription_plans.show',
            'subscription_plans.create',
            'subscription_plans.edit',
            'subscription_plans.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_can_list_plans()
    {
        SubscriptionPlan::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/admin/subscription/plans');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_plan()
    {
        $payload = [
            'name' => 'Premium Plan',
            'slug' => 'premium-plan',
            'description' => 'A premium plan',
            'price' => 99.99,
            'currency' => 'USD',
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/subscription/plans', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'premium-plan']);
    }

    public function test_can_show_plan()
    {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/admin/subscription/plans/{$plan->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $plan->id);
    }

    public function test_can_update_plan()
    {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/admin/subscription/plans/{$plan->id}", [
                'name' => 'Updated Plan',
                'price' => 149.99,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => 'Updated Plan',
            'price' => 149.99,
        ]);
    }

    public function test_can_delete_plan()
    {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/admin/subscription/plans/{$plan->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($plan);
    }

    public function test_can_sync_plan_features()
    {
        $plan = SubscriptionPlan::factory()->create();
        $featureA = SubscriptionFeature::factory()->create();
        $featureB = SubscriptionFeature::factory()->create();

        $payload = [
            'features' => [
                ['feature_id' => $featureA->id, 'is_enabled' => true, 'limit_value' => null],
                ['feature_id' => $featureB->id, 'is_enabled' => true, 'limit_value' => 10],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/subscription/plans/{$plan->id}/features", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscription_plan_features', [
            'plan_id' => $plan->id,
            'feature_id' => $featureA->id,
            'is_enabled' => true,
        ]);
        $this->assertDatabaseHas('subscription_plan_features', [
            'plan_id' => $plan->id,
            'feature_id' => $featureB->id,
            'limit_value' => 10,
        ]);
    }

    public function test_create_plan_requires_unique_slug()
    {
        SubscriptionPlan::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($this->user)->postJson('/api/admin/subscription/plans', [
            'name' => 'Another Plan',
            'slug' => 'existing-slug',
            'price' => 29.99,
            'duration_days' => 30,
        ]);

        $response->assertStatus(422);
    }

    public function test_create_plan_requires_name_and_price()
    {
        $response = $this->actingAs($this->user)->postJson('/api/admin/subscription/plans', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'slug', 'price', 'duration_days']);
    }
}
