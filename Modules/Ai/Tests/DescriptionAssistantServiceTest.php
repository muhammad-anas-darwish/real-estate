<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Ai\Services\AiService;
use Modules\Ai\Services\DescriptionAssistantService;
use Tests\TestCase;

class DescriptionAssistantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);
    }

    public function test_generate_returns_description(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'فيلا فاخرة في حي الياسمين...']]],
            ], 200),
        ]);

        $service = new DescriptionAssistantService(new AiService);
        $result = $service->generate([
            'property_type' => 'villa',
            'rooms' => 5, 'area' => 400, 'city' => 'Riyadh',
        ]);

        $this->assertStringContainsString('فيلا فاخرة', $result);
    }

    public function test_generate_returns_null_when_api_unavailable(): void
    {
        config(['services.kimi.api_key' => null]);

        $service = new DescriptionAssistantService(new AiService);
        $this->assertNull($service->generate(['property_type' => 'villa']));
    }

    public function test_improve_returns_improved_text(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'الوصف المحسّن: فيلا مميزة...']]],
            ], 200),
        ]);

        $service = new DescriptionAssistantService(new AiService);
        $result = $service->improve([
            'current_description' => 'فيلا 5 غرف في الرياض',
        ]);

        $this->assertStringContainsString('فيلا مميزة', $result);
    }

    public function test_improve_returns_null_for_short_text(): void
    {
        $service = new DescriptionAssistantService(new AiService);
        $this->assertNull($service->improve(['current_description' => 'قصير']));
    }

    public function test_suggest_titles_returns_array(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '["فيلا الأحلام", "بيت العائلة", "عش الرفاهية"]']]],
            ], 200),
        ]);

        $service = new DescriptionAssistantService(new AiService);
        $titles = $service->suggestTitles(['property_type' => 'villa', 'rooms' => 5]);

        $this->assertCount(3, $titles);
        $this->assertEquals('فيلا الأحلام', $titles[0]);
    }

    public function test_suggest_features_returns_array(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '["مسبح", "حديقة", "موقف سيارات", "مصعد", "حارس", "نظام أمان", "إطلالة", "مكيف مركزي"]']]],
            ], 200),
        ]);

        $service = new DescriptionAssistantService(new AiService);
        $features = $service->suggestFeatures(['property_type' => 'villa']);

        $this->assertCount(8, $features);
        $this->assertEquals('مسبح', $features[0]);
    }
}
