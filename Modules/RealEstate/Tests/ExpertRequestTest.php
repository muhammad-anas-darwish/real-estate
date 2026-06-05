<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Expert\Entities\ExpertRelationship;
use Modules\RealEstate\Expert\Entities\ExpertRequest;
use Modules\RealEstate\Expert\Enums\ExpertRelationshipStatus;
use Modules\RealEstate\Expert\Enums\ExpertRequestStatus;
use Modules\RealEstate\Expert\Enums\ExpertType;
use Tests\TestCase;

class ExpertRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $expert;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->user = User::factory()->create(['is_expert' => false]);
        $this->user->givePermissionTo([
            'expert_requests.list',
            'expert_requests.create',
            'expert_requests.cancel',
            'expert_requests.show',
        ]);

        $this->expert = User::factory()->create([
            'is_expert' => true,
            'expert_type' => ExpertType::Photographer->value,
            'max_users' => -1,
        ]);
    }

    public function test_user_can_create_expert_request(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/expert-requests', [
                'expert_type' => 'photographer',
                'message' => 'I need a photographer for my property',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('expert_requests', [
            'user_id' => $this->user->id,
            'expert_type' => 'photographer',
            'status' => 'pending',
        ]);
    }

    public function test_system_automatically_matches_with_available_expert(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/expert-requests', [
                'expert_type' => 'photographer',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('expert_requests', [
            'user_id' => $this->user->id,
            'status' => 'resolved',
        ]);

        $this->assertDatabaseHas('expert_relationships', [
            'expert_id' => $this->expert->id,
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_create_duplicate_pending_request(): void
    {
        ExpertRequest::factory()->create([
            'user_id' => $this->user->id,
            'expert_type' => ExpertType::Photographer,
            'status' => ExpertRequestStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/expert-requests', [
                'expert_type' => 'photographer',
            ]);

        $response->assertStatus(400);
    }

    public function test_user_can_get_their_requests(): void
    {
        ExpertRequest::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/expert-requests');

        $response->assertStatus(200);
    }

    public function test_user_can_cancel_pending_request(): void
    {
        $request = ExpertRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpertRequestStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/expert-requests/{$request->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('expert_requests', [
            'id' => $request->id,
            'status' => 'resolved',
        ]);
    }

    public function test_user_cannot_cancel_resolved_request(): void
    {
        $request = ExpertRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpertRequestStatus::Resolved,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/expert-requests/{$request->id}/cancel");

        $response->assertStatus(400);
    }

    public function test_system_selects_expert_with_least_active_users(): void
    {
        $busyExpert = User::factory()->create([
            'is_expert' => true,
            'expert_type' => ExpertType::Photographer->value,
            'max_users' => -1,
        ]);

        ExpertRelationship::factory()->count(5)->create([
            'expert_id' => $busyExpert->id,
            'status' => ExpertRelationshipStatus::Active,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/expert-requests', [
                'expert_type' => 'photographer',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('expert_relationships', [
            'expert_id' => $this->expert->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_request_remains_pending_when_no_expert_available(): void
    {
        $expertWithNoCapacity = User::factory()->create([
            'is_expert' => true,
            'expert_type' => ExpertType::Photographer->value,
            'max_users' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/expert-requests', [
                'expert_type' => 'photographer',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('expert_requests', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }
}
