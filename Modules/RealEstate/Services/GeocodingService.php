<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService extends BaseService
{
    protected const CACHE_TTL = 86400;

    protected const CACHE_TAG = 'geocoding';

    public function geocode(string $address): ?array
    {
        $cacheKey = md5($address);

        return Cache::tags([self::CACHE_TAG])->remember(
            "geocode:{$cacheKey}",
            self::CACHE_TTL,
            fn () => $this->fetchFromGoogle($address)
        );
    }

    public function reverse(float $lat, float $lng): ?string
    {
        $cacheKey = md5("{$lat},{$lng}");

        return Cache::tags([self::CACHE_TAG])->remember(
            "reverse:{$cacheKey}",
            self::CACHE_TTL,
            fn () => $this->fetchReverse($lat, $lng)
        );
    }

    protected function fetchFromGoogle(string $address): ?array
    {
        $apiKey = config('services.google.geocoding_api_key');

        if (! $apiKey) {
            Log::warning('GOOGLE_MAPS_API_KEY not set. Geocoding disabled.');

            return null;
        }

        try {
            $response = Http::timeout(5)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key' => $apiKey,
                ]);

            if (! $response->successful()) {
                Log::error('Geocoding API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
                return null;
            }

            $location = $data['results'][0]['geometry']['location'];

            return [
                'latitude' => (float) $location['lat'],
                'longitude' => (float) $location['lng'],
                'formatted_address' => $data['results'][0]['formatted_address'] ?? $address,
            ];
        } catch (\Throwable $e) {
            Log::error('Geocoding exception: '.$e->getMessage());

            return null;
        }
    }

    protected function fetchReverse(float $lat, float $lng): ?string
    {
        $apiKey = config('services.google.geocoding_api_key');

        if (! $apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$lat},{$lng}",
                    'key' => $apiKey,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            return $data['results'][0]['formatted_address'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function clearCache(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }
}
