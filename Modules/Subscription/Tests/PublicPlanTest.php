<?php

namespace Modules\Subscription\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Subscription\Entities\SubscriptionPlan;
use Tests\TestCase;

class PublicPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_plans()
    {
        SubscriptionPlan::factory()->count(3)->create();
        SubscriptionPlan::factory()->inactive()->create();

        $response = $this->getJson('/api/plans');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_show_active_plan()
    {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->getJson("/api/plans/{$plan->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', $plan->name);
    }

    public function test_cannot_show_inactive_plan()
    {
        $plan = SubscriptionPlan::factory()->inactive()->create();

        $response = $this->getJson("/api/plans/{$plan->id}");

        $response->assertStatus(404);
    }

    public function test_plans_are_ordered()
    {
        $planA = SubscriptionPlan::factory()->create(['sort_order' => 2, 'price' => 49.99]);
        $planB = SubscriptionPlan::factory()->create(['sort_order' => 1, 'price' => 19.99]);
        $planC = SubscriptionPlan::factory()->create(['sort_order' => 3, 'price' => 99.99]);

        $response = $this->getJson('/api/plans');

        $response->assertStatus(200);
        $this->assertEquals($planB->id, $response->json('data.0.id'));
        $this->assertEquals($planA->id, $response->json('data.1.id'));
        $this->assertEquals($planC->id, $response->json('data.2.id'));
    }
}
