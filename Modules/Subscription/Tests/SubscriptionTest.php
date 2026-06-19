<?php

namespace Modules\Subscription\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionPlan;
use Modules\Subscription\Enums\SubscriptionStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        foreach (['subscription_plans.list', 'subscription_plans.show'] as $perm) {
            Permission::create(['name' => $perm]);
            $role->givePermissionTo($perm);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->plan = SubscriptionPlan::factory()->create();
    }

    public function test_can_get_current_subscription()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/subscription/current');

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'active');
    }

    public function test_returns_not_found_when_no_active_subscription()
    {
        $response = $this->actingAs($this->user)->getJson('/api/subscription/current');

        $response->assertStatus(404);
    }

    public function test_can_get_subscription_history()
    {
        Subscription::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/subscription/history');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_cancel_active_subscription()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/subscription/cancel');

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'status' => SubscriptionStatus::CANCELLED->value,
        ]);
    }

    public function test_cannot_cancel_without_active_subscription()
    {
        $response = $this->actingAs($this->user)->postJson('/api/subscription/cancel');

        $response->assertStatus(400);
    }

    public function test_can_get_feature_access()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/subscription/features');

        $response->assertStatus(200);
        $response->assertJsonIsArray('data');
    }

    public function test_can_get_status_logs()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $subscription->statusLogs()->createMany([
            ['from_status' => 'pending', 'to_status' => 'active', 'reason' => 'Payment completed'],
            ['from_status' => 'active', 'to_status' => 'cancelled', 'reason' => 'User requested'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/subscription/{$subscription->id}/status-logs");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_cannot_access_other_users_subscription_logs()
    {
        $otherUser = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/subscription/{$subscription->id}/status-logs");

        $response->assertStatus(404);
    }

    public function test_checkout_requires_payment_fields()
    {
        $response = $this->actingAs($this->user)->postJson('/api/checkout', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['plan_id']);
    }

    public function test_checkout_fails_for_invalid_plan()
    {
        $response = $this->actingAs($this->user)->postJson('/api/checkout', [
            'plan_id' => 9999,
        ]);

        $response->assertStatus(422);
    }
}
