<?php

namespace Modules\Subscription\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Subscription\Entities\SubscriptionDiscount;
use Modules\Subscription\Entities\SubscriptionPlan;
use Modules\Subscription\Enums\DiscountType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'subscription_discounts.list',
            'subscription_discounts.show',
            'subscription_discounts.create',
            'subscription_discounts.edit',
            'subscription_discounts.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_can_list_discounts()
    {
        SubscriptionDiscount::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/admin/subscription/discounts');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_discount()
    {
        $plan = SubscriptionPlan::factory()->create();

        $payload = [
            'code' => 'WELCOME20',
            'type' => DiscountType::PERCENTAGE->value,
            'value' => 20,
            'plan_id' => $plan->id,
            'max_uses' => 100,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/subscription/discounts', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subscription_discounts', ['code' => 'WELCOME20']);
    }

    public function test_can_show_discount()
    {
        $discount = SubscriptionDiscount::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/admin/subscription/discounts/{$discount->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $discount->id);
    }

    public function test_can_update_discount()
    {
        $discount = SubscriptionDiscount::factory()->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/admin/subscription/discounts/{$discount->id}", [
                'value' => 25,
                'is_active' => false,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscription_discounts', [
            'id' => $discount->id,
            'value' => 25,
            'is_active' => false,
        ]);
    }

    public function test_can_delete_discount()
    {
        $discount = SubscriptionDiscount::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/admin/subscription/discounts/{$discount->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('subscription_discounts', ['id' => $discount->id]);
    }

    public function test_can_validate_valid_coupon()
    {
        $plan = SubscriptionPlan::factory()->create(['price' => 100]);
        $discount = SubscriptionDiscount::factory()->percentage()->create([
            'code' => 'SAVE20',
            'value' => 20,
            'plan_id' => $plan->id,
        ]);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'SAVE20',
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.valid', true);
        $response->assertJsonPath('data.pricing.final_price', 80);
    }

    public function test_returns_error_for_invalid_coupon()
    {
        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'INVALID',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
    }

    public function test_returns_error_for_expired_coupon()
    {
        $discount = SubscriptionDiscount::factory()->expired()->create(['code' => 'EXPIRED']);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'EXPIRED',
        ]);

        $response->assertStatus(400);
    }

    public function test_returns_error_for_exhausted_coupon()
    {
        $discount = SubscriptionDiscount::factory()->exhausted()->create(['code' => 'USEDUP']);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'USEDUP',
        ]);

        $response->assertStatus(400);
    }

    public function test_returns_error_for_inactive_coupon()
    {
        $discount = SubscriptionDiscount::factory()->inactive()->create(['code' => 'INACTIVE']);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'INACTIVE',
        ]);

        $response->assertStatus(400);
    }

    public function test_returns_error_when_coupon_not_applicable_to_plan()
    {
        $planA = SubscriptionPlan::factory()->create();
        $planB = SubscriptionPlan::factory()->create();
        $discount = SubscriptionDiscount::factory()->create([
            'code' => 'PLANONLY',
            'plan_id' => $planA->id,
        ]);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'PLANONLY',
            'plan_id' => $planB->id,
        ]);

        $response->assertStatus(400);
    }
}
