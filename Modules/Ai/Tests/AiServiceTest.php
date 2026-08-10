<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Ai\Services\AiService;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);
    }

    public function test_chat_returns_null_when_api_key_missing(): void
    {
        config(['services.kimi.api_key' => null]);
        $service = new AiService;
        $this->assertNull($service->chat('system', 'user'));
    }

    public function test_chat_returns_content_on_success(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'مرحبا']]],
            ], 200),
        ]);

        $service = new AiService;
        $result = $service->chat('أنت مساعد', 'مرحبا');

        $this->assertEquals('مرحبا', $result);
    }

    public function test_chat_returns_null_on_401(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $service = new AiService;
        $this->assertNull($service->chat('system', 'user'));
    }

    public function test_chat_retries_on_500_then_succeeds(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::sequence()
                ->push(['error' => 'server'], 500)
                ->push(['choices' => [['message' => ['content' => 'ok']]]], 200),
        ]);

        $service = new AiService;
        $this->assertEquals('ok', $service->chat('s', 'u'));
        Http::assertSentCount(2);
    }

    public function test_chat_json_extracts_from_code_fence(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => [
                    'content' => '```json'."\n".'{"type":"apartment","rooms":3}'."\n".'```',
                ]]],
            ], 200),
        ]);

        $service = new AiService;
        $result = $service->chatJson('s', 'u');

        $this->assertEquals('apartment', $result['type']);
        $this->assertEquals(3, $result['rooms']);
    }

    public function test_chat_json_returns_null_on_invalid_json(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'not json']]],
            ], 200),
        ]);

        $service = new AiService;
        $this->assertNull($service->chatJson('s', 'u'));
    }

    public function test_is_available_returns_true_when_key_set(): void
    {
        $service = new AiService;
        $this->assertTrue($service->isAvailable());
    }

    public function test_is_available_returns_false_when_key_missing(): void
    {
        config(['services.kimi.api_key' => null]);
        $service = new AiService;
        $this->assertFalse($service->isAvailable());
    }

    public function test_routes_are_registered(): void
    {
        $routes = collect(Route::getRoutes())
            ->map(fn ($r) => $r->uri())
            ->toArray();

        $this->assertContains('api/ai/search', $routes);
        $this->assertContains('api/ai/description/generate', $routes);
        $this->assertContains('api/ai/description/improve', $routes);
        $this->assertContains('api/ai/description/suggest-title', $routes);
        $this->assertContains('api/ai/description/suggest-features', $routes);
    }
}
