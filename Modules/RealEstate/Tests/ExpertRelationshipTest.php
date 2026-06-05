<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\RealEstate\Expert\Entities\ExpertRelationship;
use Modules\RealEstate\Expert\Enums\ExpertRelationshipStatus;
use Modules\RealEstate\Expert\Enums\ExpertType;
use Tests\TestCase;

class ExpertRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $expert;
    protected ExpertRelationship $relationship;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->user = User::factory()->create(['is_expert' => false]);
        $this->user->givePermissionTo([
            'expert_relationships.list',
            'expert_relationships.cancel',
            'expert_relationships.show',
        ]);

        $this->expert = User::factory()->create([
            'is_expert' => true,
            'expert_type' => ExpertType::Photographer->value,
            'max_users' => -1,
        ]);
        $this->expert->givePermissionTo([
            'expert_relationships.list',
            'expert_relationships.cancel',
            'expert_relationships.complete',
            'expert_relationships.show',
        ]);

        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->expert->id]);

        $this->relationship = ExpertRelationship::factory()->create([
            'expert_id' => $this->expert->id,
            'user_id' => $this->user->id,
            'room_id' => $room->id,
            'status' => ExpertRelationshipStatus::Active,
        ]);
    }

    public function test_user_can_get_their_relationships(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/expert-relationships');

        $response->assertStatus(200);
    }

    public function test_expert_can_get_their_relationships(): void
    {
        $response = $this->actingAs($this->expert)
            ->getJson('/api/expert-relationships');

        $response->assertStatus(200);
    }

    public function test_user_can_cancel_their_relationship(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/expert-relationships/{$this->relationship->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('expert_relationships', [
            'id' => $this->relationship->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_expert_can_cancel_their_relationship(): void
    {
        $response = $this->actingAs($this->expert)
            ->putJson("/api/expert-relationships/{$this->relationship->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('expert_relationships', [
            'id' => $this->relationship->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_expert_can_complete_their_relationship(): void
    {
        $response = $this->actingAs($this->expert)
            ->putJson("/api/expert-relationships/{$this->relationship->id}/complete");

        $response->assertStatus(200);
        $this->assertDatabaseHas('expert_relationships', [
            'id' => $this->relationship->id,
            'status' => 'completed',
        ]);
    }

    public function test_user_cannot_complete_relationship(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/expert-relationships/{$this->relationship->id}/complete");

        $response->assertStatus(403);
    }

    public function test_cannot_cancel_completed_relationship(): void
    {
        $this->relationship->update(['status' => ExpertRelationshipStatus::Completed]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/expert-relationships/{$this->relationship->id}/cancel");

        $response->assertStatus(400);
    }
}
