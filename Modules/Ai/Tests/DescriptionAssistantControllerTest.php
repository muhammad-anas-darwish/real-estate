<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Auth\Entities\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DescriptionAssistantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);

        Permission::firstOrCreate(['name' => 'properties.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'properties.edit', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo(['properties.create', 'properties.edit']);

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');
    }

    public function test_generate_endpoint_requires_auth(): void
    {
        $response = $this->postJson('/api/ai/description/generate', ['property_type' => 'villa']);
        $response->assertStatus(401);
    }

    public function test_generate_endpoint_works_for_trader(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'فيلا فاخرة']]],
            ], 200),
        ]);

        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/generate', [
                'property_type' => 'villa',
                'rooms' => 5,
                'city' => 'Riyadh',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('فيلا فاخرة', $response->json('data.description'));
    }

    public function test_generate_returns_503_when_ai_unavailable(): void
    {
        config(['services.kimi.api_key' => null]);

        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/generate', ['property_type' => 'villa']);

        $response->assertStatus(503);
    }

    public function test_improve_validates_current_description_required(): void
    {
        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/improve', []);

        $response->assertStatus(422);
    }

    public function test_suggest_title_returns_three_titles(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '["عنوان 1", "عنوان 2", "عنوان 3"]']]],
            ], 200),
        ]);

        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/suggest-title', [
                'property_type' => 'villa', 'rooms' => 5,
            ]);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.titles'));
    }

    public function test_suggest_features_requires_property_type(): void
    {
        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/suggest-features', []);

        $response->assertStatus(422);
    }

    public function test_non_trader_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/ai/description/generate', ['property_type' => 'villa']);

        $response->assertStatus(403);
    }
}
