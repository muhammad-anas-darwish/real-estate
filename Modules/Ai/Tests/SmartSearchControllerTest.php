<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Tests\TestCase;

class SmartSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);
        \Illuminate\Support\Facades\Cache::tags([\Modules\Ai\Services\SmartSearchService::CACHE_TAG])->flush();
        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_search_endpoint_is_public(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '{}']]],
            ], 200),
        ]);

        $response = $this->postJson('/api/ai/search', ['query' => 'شقة في الرياض']);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['properties', 'extracted_requirements', 'total_matching', 'returned', 'language'],
        ]);
    }

    public function test_search_validates_min_length(): void
    {
        $response = $this->postJson('/api/ai/search', ['query' => 'abc']);
        $response->assertStatus(422);
    }

    public function test_search_validates_max_length(): void
    {
        $response = $this->postJson('/api/ai/search', ['query' => str_repeat('a', 501)]);
        $response->assertStatus(422);
    }

    public function test_search_falls_back_on_ai_error(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response(['error' => 'server'], 500),
        ]);

        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'name' => 'فيلا فاخرة',
        ]);

        $response = $this->postJson('/api/ai/search', ['query' => 'فيلا فاخرة']);

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.fallback'));
    }
}
