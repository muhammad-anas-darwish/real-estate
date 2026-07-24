# المهمة #30: Geocoding وتأسيس بيانات الخريطة (Map Data Foundation)

> **التقرير المصدر:** `docs/reports/04-properties-map-view.md` (السيناريوهات 1–5، القواعد 1–15، معايير القبول)
> **الهدف:** إنشاء البنية الأساسية للـ Map في الـ Backend: GeocodingService لتحويل العناوين إلى إحداثيات تلقائياً، endpoint للبيانات مع فلاتر، MapFilterDTO، MapPropertyResource، MapBounds value object، cache للإحداثيات.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 عالية
> **الجهد المقدّر:** 4–6 ساعات
> **الاعتمادية:** لا شيء — هذه الخطة هي الأساس.
> **راجع:** `docs/reports/04-properties-map-view.md`

---

## الوضع الحالي

الـ Property model يحوي `latitude` و `longitude` كحقول (منذ migration الأصلي) لكن:
- لا يوجد Geocoding تلقائي — التاجر يجب أن يدخل الإحداثيات يدوياً.
- لا يوجد endpoint مخصّص للـ Map.
- لا يوجد DTO لتوحيد فلاتر الخريطة.

هذه الخطة تنشئ:
- `GeocodingService` يحوّل عنوان → (lat, lng) عبر Google Geocoding API.
- `MapBounds` value object لحساب الـ bounding box.
- `MapFilterDTO` لتوحيد الفلاتر.
- `MapPropertyResource` لاستجابة موحّدة.
- `MapController` مع `properties()` endpoint (للخريطة).
- ربط `GeocodingService` بـ `PropertyService` (يملأ lat/lng عند الحفظ إذا فاضي).
- اختبارات.

---

## المرحلة 1: Configuration (~ 30 دقيقة)

### 1.1 `config/services.php` — إضافة Google Maps block

في `config/services.php` أضف:
```php
'google' => [
    'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    'geocoding_api_key' => env('GOOGLE_MAPS_API_KEY'), // نفس المفتاح
],
```

### 1.2 `.env.example` — إضافة

```
GOOGLE_MAPS_API_KEY=
```

---

## المرحلة 2: `GeocodingService` (~ 2 ساعة)

`Modules/RealEstate/Services/GeocodingService.php`:

```php
<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService extends BaseService
{
    protected const CACHE_TTL = 86400; // يوم كامل (العناوين لا تتغير)

    protected const CACHE_TAG = 'geocoding';

    public function geocode(string $address): ?array
    {
        $cacheKey = md5($address);

        return Cache::tags([self::CACHE_TAG])->remember(
            "geocode:{$cacheKey}",
            self::CACHE_TTL,
            fn() => $this->fetchFromGoogle($address)
        );
    }

    public function reverse(float $lat, float $lng): ?string
    {
        $cacheKey = md5("{$lat},{$lng}");

        return Cache::tags([self::CACHE_TAG])->remember(
            "reverse:{$cacheKey}",
            self::CACHE_TTL,
            fn() => $this->fetchReverse($lat, $lng)
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
```

---

## المرحلة 3: `MapBounds` value object (~ 1 ساعة)

`Modules/RealEstate/ValueObjects/MapBounds.php`:

```php
<?php

namespace Modules\RealEstate\ValueObjects;

final readonly class MapBounds
{
    public function __construct(
        public float $swLat,
        public float $swLng,
        public float $neLat,
        public float $neLng,
    ) {
        if ($swLat > $neLat || $swLng > $neLng) {
            throw new \InvalidArgumentException('Invalid MapBounds: SW must be less than NE');
        }
    }

    /**
     * حساب bounding box من نقطة مركزية ونصف قطر (بالكيلومتر).
     */
    public static function fromCenter(float $centerLat, float $centerLng, float $radiusKm): self
    {
        $earthRadiusKm = 6371;

        // Approximate degrees per km
        $latDelta = $radiusKm / 111; // 1 degree latitude ≈ 111 km
        $lngDelta = $radiusKm / (111 * cos(deg2rad($centerLat)));

        return new self(
            swLat: $centerLat - $latDelta,
            swLng: $centerLng - $lngDelta,
            neLat: $centerLat + $latDelta,
            neLng: $centerLng + $lngDelta,
        );
    }

    public function contains(float $lat, float $lng): bool
    {
        return $lat >= $this->swLat && $lat <= $this->neLat
            && $lng >= $this->swLng && $lng <= $this->neLng;
    }

    public function toArray(): array
    {
        return [
            'sw' => ['lat' => $this->swLat, 'lng' => $this->swLng],
            'ne' => ['lat' => $this->neLat, 'lng' => $this->neLng],
        ];
    }
}
```

---

## المرحلة 4: `MapFilterDTO` (~ 1 ساعة)

`Modules/RealEstate/DTOs/MapFilterDTO.php`:

```php
<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;
use Carbon\Carbon;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;
use Modules\RealEstate\ValueObjects\MapBounds;

readonly final class MapFilterDTO implements DTOInterface
{
    public function __construct(
        public ?PropertyType $propertyType = null,
        public ?TypeOfContract $contractType = null,
        public ?float $priceMin = null,
        public ?float $priceMax = null,
        public ?int $roomsMin = null,
        public ?int $areaMin = null,
        public ?int $cityId = null,
        public ?int $countryId = null,
        public ?int $publisherType = null,
        public ?int $createdWithinDays = null,
        public ?MapBounds $bounds = null,
        public ?float $centerLat = null,
        public ?float $centerLng = null,
        public ?float $radiusKm = null,
        public int $limit = 500,
    ) {}

    public static function fromRequest(array $data): self
    {
        $bounds = null;
        if (isset($data['sw_lat'], $data['sw_lng'], $data['ne_lat'], $data['ne_lng'])) {
            $bounds = new MapBounds(
                swLat: (float) $data['sw_lat'],
                swLng: (float) $data['sw_lng'],
                neLat: (float) $data['ne_lat'],
                neLng: (float) $data['ne_lng'],
            );
        } elseif (isset($data['center_lat'], $data['center_lng'], $data['radius_km'])) {
            $bounds = MapBounds::fromCenter(
                (float) $data['center_lat'],
                (float) $data['center_lng'],
                (float) $data['radius_km'],
            );
        }

        return new self(
            propertyType: isset($data['property_type']) ? PropertyType::from($data['property_type']) : null,
            contractType: isset($data['contract_type']) ? TypeOfContract::from($data['contract_type']) : null,
            priceMin: isset($data['price_min']) ? (float) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (float) $data['price_max'] : null,
            roomsMin: isset($data['rooms_min']) ? (int) $data['rooms_min'] : null,
            areaMin: isset($data['area_min']) ? (int) $data['area_min'] : null,
            cityId: isset($data['city_id']) ? (int) $data['city_id'] : null,
            countryId: isset($data['country_id']) ? (int) $data['country_id'] : null,
            publisherType: isset($data['publisher_type']) ? (int) $data['publisher_type'] : null,
            createdWithinDays: isset($data['created_within_days']) ? (int) $data['created_within_days'] : null,
            bounds: $bounds,
            centerLat: isset($data['center_lat']) ? (float) $data['center_lat'] : null,
            centerLng: isset($data['center_lng']) ? (float) $data['center_lng'] : null,
            radiusKm: isset($data['radius_km']) ? (float) $data['radius_km'] : null,
            limit: min(500, max(1, (int) ($data['limit'] ?? 500))),
        );
    }

    public function toArray(): array
    {
        return [
            'property_type' => $this->propertyType?->value,
            'contract_type' => $this->contractType?->value,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
            'rooms_min' => $this->roomsMin,
            'area_min' => $this->areaMin,
            'city_id' => $this->cityId,
            'country_id' => $this->countryId,
            'publisher_type' => $this->publisherType,
            'created_within_days' => $this->createdWithinDays,
            'bounds' => $this->bounds?->toArray(),
            'center_lat' => $this->centerLat,
            'center_lng' => $this->centerLng,
            'radius_km' => $this->radiusKm,
            'limit' => $this->limit,
        ];
    }
}
```

---

## المرحلة 5: `MapPropertyResource` (~ 30 دقيقة)

`Modules/RealEstate/Http/Resources/MapPropertyResource.php`:

```php
<?php

namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MapPropertyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'property_type' => $this->property_type?->value,
            'type_of_contract' => $this->type_of_contract?->value,
            'rooms' => $this->rooms,
            'bathrooms' => $this->bathrooms,
            'area' => (float) $this->area,
            'city_id' => $this->city_id,
            'city_name' => $this->city?->name,
            'main_image' => $this->main_image_url,
            'publisher_id' => $this->publisher_id,
            'is_physically_verified' => (bool) $this->is_physically_verified,
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
```

---

## المرحلة 6: `MapController` + Routes (~ 1 ساعة)

### 6.1 `Modules/RealEstate/Http/Controllers/MapController.php`

```php
<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\DTOs\MapFilterDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Http\Resources\MapPropertyResource;

class MapController extends Controller
{
    use ApiResponses, ApplyPermissions;

    public function __construct()
    {
        $this->applyPermissions(
            'properties',
            [],
            [
                'properties' => 'list',
            ]
        );
    }

    public function properties(Request $request): JsonResponse
    {
        $filter = MapFilterDTO::fromRequest($request->all());

        $query = Property::query()
            ->where('status', PropertyStatus::APPROVED)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($filter->propertyType) {
            $query->where('property_type', $filter->propertyType);
        }
        if ($filter->contractType) {
            $query->where('type_of_contract', $filter->contractType);
        }
        if ($filter->priceMin !== null) {
            $query->where('price', '>=', $filter->priceMin);
        }
        if ($filter->priceMax !== null) {
            $query->where('price', '<=', $filter->priceMax);
        }
        if ($filter->roomsMin !== null) {
            $query->where('rooms', '>=', $filter->roomsMin);
        }
        if ($filter->areaMin !== null) {
            $query->where('area', '>=', $filter->areaMin);
        }
        if ($filter->cityId) {
            $query->where('city_id', $filter->cityId);
        }
        if ($filter->countryId) {
            $query->where('country_id', $filter->countryId);
        }
        if ($filter->publisherType !== null) {
            $query->where('publisher_type', $filter->publisherType);
        }
        if ($filter->createdWithinDays) {
            $query->where('created_at', '>=', now()->subDays($filter->createdWithinDays));
        }
        if ($filter->bounds) {
            $query->whereBetween('latitude', [$filter->bounds->swLat, $filter->bounds->neLat])
                ->whereBetween('longitude', [$filter->bounds->swLng, $filter->bounds->neLng]);
        }

        $totalMatching = (clone $query)->count();
        $properties = $query->with('city:id,name')
            ->limit($filter->limit)
            ->get();

        $isTruncated = $totalMatching > $filter->limit;

        return $this->successResponse([
            'properties' => MapPropertyResource::collection($properties),
            'meta' => [
                'total_matching' => $totalMatching,
                'returned' => $properties->count(),
                'is_truncated' => $isTruncated,
                'limit' => $filter->limit,
                'truncation_message' => $isTruncated
                    ? "يتم عرض {$filter->limit} من {$totalMatching} عقار. كبّر الخريطة أو طبّق فلاتر أضيق."
                    : null,
            ],
            'filter' => $filter->toArray(),
        ]);
    }
}
```

### 6.2 Routes — أضف في `Modules/RealEstate/Routes/api.php`

```php
// Map (public)
Route::get('map/properties', [MapController::class, 'properties']);
```

> ملاحظة: route بدون middleware `auth:sanctum` (public).

---

## المرحلة 7: ربط Geocoding بـ PropertyService (~ 1 ساعة)

> في `PropertyService::create()` / `update()`، إذا كان `latitude` أو `longitude` فارغ وكان `country` و`city` و`address` معروفين، اتصل بـ `GeocodingService::geocode()`.

افتح `Modules/RealEstate/Services/PropertyService.php` وأضف المنطق (المنطق الدقيق يعتمد على البنية الموجودة).

الخطوات العامة:
1. حقن `GeocodingService` في constructor.
2. قبل `save()`، إذا `$property->latitude === null && $property->city && $property->country`:
   - ابن العنوان: `"{$property->city->name}, {$property->country->name}"`.
   - استدعِ `geocodingService->geocode($address)`.
   - إذا نجح: املأ `latitude` و `longitude`.
3. إذا فشل: سجّل في الـ log (لا تكسر الحفظ).

---

## المرحلة 8: اختبارات (~ 2 ساعة)

### 8.1 `GeocodingServiceTest`

`Modules/RealEstate/Tests/GeocodingServiceTest.php`:

```php
<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\RealEstate\Services\GeocodingService;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocode_returns_null_when_api_key_not_set(): void
    {
        config(['services.google.geocoding_api_key' => null]);

        $service = new GeocodingService();
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

        $service = new GeocodingService();
        $result = $service->geocode('Riyadh, Saudi Arabia');

        $this->assertNotNull($result);
        $this->assertEquals(24.7136, $result['latitude']);
        $this->assertEquals(46.6753, $result['longitude']);
    }

    public function test_geocode_returns_null_on_api_error(): void
    {
        config(['services.google.geocoding_api_key' => 'test_key']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'ZERO_RESULTS', 'results' => []]),
        ]);

        $service = new GeocodingService();
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

        $service = new GeocodingService();
        $service->geocode('Jeddah');
        $service->geocode('Jeddah'); // يُقرأ من الكاش

        Http::assertSentCount(1);
    }
}
```

### 8.2 `MapBoundsTest`

```php
<?php

namespace Modules\RealEstate\Tests;

use Modules\RealEstate\ValueObjects\MapBounds;
use Tests\TestCase;

class MapBoundsTest extends TestCase
{
    public function test_from_center_creates_correct_bounds(): void
    {
        $bounds = MapBounds::fromCenter(24.7136, 46.6753, 5.0);

        $this->assertLessThan(24.7136, $bounds->swLat);
        $this->assertGreaterThan(24.7136, $bounds->neLat);
    }

    public function test_contains_returns_true_for_point_inside(): void
    {
        $bounds = new MapBounds(24.0, 46.0, 25.0, 47.0);
        $this->assertTrue($bounds->contains(24.5, 46.5));
    }

    public function test_contains_returns_false_for_point_outside(): void
    {
        $bounds = new MapBounds(24.0, 46.0, 25.0, 47.0);
        $this->assertFalse($bounds->contains(26.0, 46.5));
    }

    public function test_invalid_bounds_throw_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MapBounds(25.0, 46.0, 24.0, 47.0);
    }
}
```

### 8.3 `MapControllerTest`

```php
<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'properties.list', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo('properties.list');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_map_properties_is_public(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $response = $this->getJson('/api/map/properties');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.properties'));
    }

    public function test_excludes_pending_properties(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::PENDING,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $response = $this->getJson('/api/map/properties');

        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_excludes_properties_without_coordinates(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->getJson('/api/map/properties');
        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_filter_by_price_range(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7,
            'longitude' => 46.7,
            'price' => 100000,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7,
            'longitude' => 46.7,
            'price' => 500000,
        ]);

        $response = $this->getJson('/api/map/properties?price_min=200000&price_max=400000');

        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_truncation_message(): void
    {
        Property::factory()->count(10)->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7,
            'longitude' => 46.7,
        ]);

        $response = $this->getJson('/api/map/properties?limit=5');

        $this->assertTrue($response->json('data.meta.is_truncated'));
        $this->assertStringContainsString('5 من 10', $response->json('data.meta.truncation_message'));
    }
}
```

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Config) | مستقل | لا شيء |
| 2 (Geocoding) | مستقل | لا شيء |
| 3 (MapBounds) | مستقل | لا شيء |
| 4 (MapFilterDTO) | مستقل | لا شيء |
| 5 (MapPropertyResource) | مستقل | لا شيء |
| 6 (Controller + Routes) | يعتمد على 3+4+5 | 3 + 4 + 5 |
| 7 (PropertyService) | مستقل | 2 |
| 8 (Tests) | يعتمد على 2+3+6 | 2 + 3 + 6 |

---

## معايير القبول

- [ ] `config/services.php` يحوي `google.maps_api_key`.
- [ ] `.env.example` يحوي `GOOGLE_MAPS_API_KEY=`.
- [ ] `GeocodingService` يعمل ويخزّن النتائج في الكاش.
- [ ] `MapBounds::fromCenter()` يحسب bounding box صحيح.
- [ ] `MapFilterDTO` يطبّق DTOInterface مع `fromRequest()` و `toArray()`.
- [ ] `GET /api/map/properties` يعمل public.
- [ ] العقارات المعتمدة فقط تظهر، العقارات بدون إحداثيات تُخفى.
- [ ] الفلاتر تعمل: property_type, contract_type, price_min/max, rooms_min, area_min, city_id, country_id, publisher_type, created_within_days.
- [ ] Bounds filter يعمل (sw_lat, sw_lng, ne_lat, ne_lng).
- [ ] Radius filter يعمل (center_lat, center_lng, radius_km).
- [ ] حد 500 عقار مع رسالة truncation عند التجاوز.
- [ ] `MapPropertyResource` يُعيد الحقول الموحّدة.
- [ ] GeocodingService مربوط بـ PropertyService (يملأ الإحداثيات عند الحفظ).
- [ ] اختبارات `GeocodingServiceTest` (4 tests) تمر.
- [ ] اختبارات `MapBoundsTest` (4 tests) تمر.
- [ ] اختبارات `MapControllerTest` (5 tests) تمر.
- [ ] `composer pint` يمر.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
