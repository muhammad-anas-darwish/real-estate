<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Ai\Services\AiService;
use Modules\Ai\Services\SmartSearchService;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Tests\TestCase;

class SmartSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);
        \Illuminate\Support\Facades\Cache::tags([SmartSearchService::CACHE_TAG])->flush();

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
    }

    public function test_search_extracts_arabic_requirements(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'property_type' => 'apartment',
                    'rooms_min' => 3,
                    'city' => 'Riyadh',
                    'price_max' => 500000,
                ])]]],
            ], 200),
        ]);

        $cityId = City::where('name', 'Riyadh')->first()->id;

        $p1 = Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'property_type' => PropertyType::Apartment,
            'rooms' => 3, 'area' => 150, 'price' => 400000,
            'latitude' => 24.7, 'longitude' => 46.7,
            'city_id' => $cityId,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'property_type' => PropertyType::Villa,
            'rooms' => 5, 'price' => 800000,
            'city_id' => $cityId,
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('أريد شقة 3 غرف في الرياض أقل من 500 ألف');

        $this->assertEquals('ar', $result['language']);
        $this->assertNotNull($result['extracted_requirements']);
        $this->assertEquals('apartment', $result['extracted_requirements']['property_type']);
        $this->assertCount(1, $result['properties']);
    }

    public function test_search_detects_english(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'property_type' => 'villa',
                ])]]],
            ], 200),
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('I want a villa in Jeddah');

        $this->assertEquals('en', $result['language']);
    }

    public function test_search_falls_back_on_ai_failure(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response(['error' => 'server'], 500),
        ]);

        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'name' => 'شقة فاخرة',
            'description' => 'شقة قريبة من مدرسة',
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('شقة فاخرة');

        $this->assertTrue($result['fallback']);
        $this->assertNotEmpty($result['properties']);
    }

    public function test_search_falls_back_when_api_key_missing(): void
    {
        config(['services.kimi.api_key' => null]);

        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'name' => 'فيلا مع مسبح',
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('فيلا مع مسبح');

        $this->assertTrue($result['fallback']);
    }

    public function test_search_ranks_by_match_score(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'property_type' => 'apartment',
                    'rooms_min' => 3,
                    'city' => 'Riyadh',
                ])]]],
            ], 200),
        ]);

        $cityId = City::where('name', 'Riyadh')->first()->id;

        $a = Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'property_type' => PropertyType::Apartment,
            'rooms' => 3, 'city_id' => $cityId,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'property_type' => PropertyType::Villa,
            'rooms' => 3, 'city_id' => $cityId,
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('شقة 3 غرف في الرياض');

        $this->assertEquals($a->id, $result['properties'][0]->id);
        $this->assertEquals(100, $result['properties'][0]->match_score);
    }

    public function test_search_caches_results(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '{}']]],
            ], 200),
        ]);

        $service = new SmartSearchService(new AiService);
        $service->search('شقة');
        $service->search('شقة');

        Http::assertSentCount(1);
    }

    public function test_search_handles_invalid_property_type(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'property_type' => 'invalid_type_xyz',
                ])]]],
            ], 200),
        ]);

        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'property_type' => PropertyType::Apartment,
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('بحث');

        // Should not crash; returns all properties (no filter applied)
        $this->assertNotEmpty($result['properties']);
    }
}
