<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\RealEstate\Services\GeocodingService;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocode_returns_null_when_api_key_not_set(): void
    {
        config(['services.google.geocoding_api_key' => null]);

        $service = new GeocodingService;
        $result = $service->geocode('Riyadh, Saudi Arabia');

        $this->assertNull($result);
    }

    public function test_geocode_returns_coordinates_on_success(): void
    {
        config(['services.google.geocoding_api_key' => 'test_key']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Riyadh, Saudi Arabia',
                    'geometry' => ['location' => ['lat' => 24.7136, 'lng' => 46.6753]],
                ]],
            ]),
        ]);

        $service = new GeocodingService;
        $result = $service->geocode('Riyadh, Saudi Arabia');

        $this->assertNotNull($result);
        $this->assertEquals(24.7136, $result['latitude']);
        $this->assertEquals(46.6753, $result['longitude']);
    }

    public function test_geocode_returns_null_on_zero_results(): void
    {
        config(['services.google.geocoding_api_key' => 'test_key']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'ZERO_RESULTS', 'results' => []]),
        ]);

        $service = new GeocodingService;
        $this->assertNull($service->geocode('Invalid Address XYZ'));
    }

    public function test_geocode_caches_results(): void
    {
        config(['services.google.geocoding_api_key' => 'test_key']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Jeddah',
                    'geometry' => ['location' => ['lat' => 21.4858, 'lng' => 39.1925]],
                ]],
            ]),
        ]);

        $service = new GeocodingService;
        $service->geocode('Jeddah');
        $service->geocode('Jeddah');

        Http::assertSentCount(1);
    }

    public function test_reverse_geocode_returns_address(): void
    {
        config(['services.google.geocoding_api_key' => 'test_key']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'King Fahd Rd, Riyadh',
                ]],
            ]),
        ]);

        $service = new GeocodingService;
        $result = $service->reverse(24.7136, 46.6753);

        $this->assertEquals('King Fahd Rd, Riyadh', $result);
    }
}
