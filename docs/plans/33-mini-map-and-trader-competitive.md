# المهمة #33: خريطة مصغّرة + لوحة "تحليل السوق" للتجار

> **التقرير المصدر:** `docs/reports/04-properties-map-view.md` (السيناريوهات 3، 4، القواعد 12، 13، معايير القبول 15، 21)
> **الهدف:** إضافة خريطة Google Maps مصغّرة في صفحة تفاصيل العقار + رابط "اعرض على الخريطة" + لوحة "تحليل السوق" في dashboard التاجر تعرض خريطة تنافسية.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟡 متوسطة
> **الجهد المقدّر:** 4–6 ساعات
> **الاعتمادية:** يجب أن يسبقها #31 (Map Explorer).
> **راجع:** `docs/reports/04-properties-map-view.md`

> **ملاحظة:** هذه الخطة Frontend-heavy. تركّز على:
> 1. Backend: endpoint واحد للـ Trader Competitive Map (مع تمييز عقارات التاجر).
> 2. Frontend: توثيق تفصيلي لمكون PropertyMiniMap + TraderCompetitiveMap.

---

## الوضع الحالي

- صفحة تفاصيل العقار لا تحوي خريطة.
- لوحة التاجر لا تحوي تحليل سوق بصري.
- لا يوجد endpoint للـ competitive analysis.

هذه الخطة:
- Backend: `traderCompetitiveMap()` في MapController (لوحة التاجر).
- Frontend: توثيق تفصيلي لـ PropertyMiniMap + TraderCompetitiveMap.

---

## المرحلة 1: Backend - Trader Competitive Map endpoint (~ 1.5 ساعة)

### 1.1 Route جديد

في `Modules/RealEstate/Routes/api.php`:

```php
Route::prefix('api/dashboard/trader')->middleware(['auth:sanctum', 'trader'])->group(function () {
    // ... existing routes
    Route::get('competitive-map', [MapController::class, 'traderCompetitiveMap']);
});
```

### 1.2 Method في `MapController`

أضف method جديد:

```php
public function traderCompetitiveMap(Request $request): JsonResponse
{
    $traderId = auth()->id();
    $cityId = $request->input('city_id');
    $propertyType = $request->input('property_type');

    // عقارات التاجر نفسه
    $myProperties = Property::where('publisher_id', $traderId)
        ->where('status', PropertyStatus::APPROVED)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->when($cityId, fn($q) => $q->where('city_id', $cityId))
        ->when($propertyType, fn($q) => $q->where('property_type', $propertyType))
        ->get();

    // عقارات المنافسين
    $competitorProperties = Property::where('publisher_id', '!=', $traderId)
        ->where('status', PropertyStatus::APPROVED)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->when($cityId, fn($q) => $q->where('city_id', $cityId))
        ->when($propertyType, fn($q) => $q->where('property_type', $propertyType))
        ->limit(500)
        ->get();

    return $this->successResponse([
        'my_properties' => MapPropertyResource::collection($myProperties),
        'competitor_properties' => MapPropertyResource::collection($competitorProperties),
        'meta' => [
            'my_count' => $myProperties->count(),
            'competitor_count' => $competitorProperties->count(),
            'is_competitor_truncated' => $competitorProperties->count() >= 500,
        ],
    ]);
}
```

### 1.3 Permission جديد

في `PermissionSeeder`:
```php
'statistics' => ['view', 'export'],  // existing
// لا حاجة — نستخدم properties.list الموجود
```

فعلياً: trader لديه `properties.list` من الـ seeder. الـ endpoint محمي بـ `'trader'` middleware.

---

## المرحلة 2: Backend tests (~ 1.5 ساعة)

`Modules/RealEstate/Tests/TraderCompetitiveMapTest.php`:

```php
<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TraderCompetitiveMapTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'properties.list', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo('properties.list');

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_returns_my_and_competitor_properties_separately(): void
    {
        Property::factory()->count(3)->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        Property::factory()->count(5)->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/competitive-map');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.my_properties'));
        $this->assertCount(5, $response->json('data.competitor_properties'));
        $this->assertEquals(3, $response->json('data.meta.my_count'));
        $this->assertEquals(5, $response->json('data.meta.competitor_count'));
    }

    public function test_excludes_own_pending_properties(): void
    {
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::PENDING,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/competitive-map');

        $this->assertCount(1, $response->json('data.my_properties'));
    }

    public function test_excludes_properties_without_coordinates(): void
    {
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => null, 'longitude' => null,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/competitive-map');

        $this->assertCount(0, $response->json('data.my_properties'));
    }

    public function test_filter_by_city(): void
    {
        $city = City::first();
        $otherCity = City::factory()->create(['country_id' => $city->country_id]);

        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'city_id' => $city->id,
        ]);
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'city_id' => $otherCity->id,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/trader/competitive-map?city_id={$city->id}");

        $this->assertCount(1, $response->json('data.my_properties'));
    }

    public function test_requires_trader_role(): void
    {
        $response = $this->getJson('/api/dashboard/trader/competitive-map');
        $response->assertStatus(401);
    }
}
```

---

## المرحلة 3: Frontend - مرجع PropertyMiniMap + TraderCompetitiveMap (~ 1.5 ساعة)

`docs/frontend/map-integrations-frontend.md`:

```markdown
# دليل Frontend: تكاملات الخريطة

## 1. PropertyMiniMap (في صفحة تفاصيل العقار)

### الاستخدام
```jsx
// في PropertyDetails.tsx
import { PropertyMiniMap } from '@/components/PropertyMiniMap';

<section className="location-section">
  <h2>الموقع</h2>
  <PropertyMiniMap
    latitude={property.latitude}
    longitude={property.longitude}
    propertyName={property.name}
  />
  <Link href={`/map?property=${property.id}&focus=true`}>
    اعرض على الخريطة
  </Link>
</section>
```

### المكون
```jsx
// components/PropertyMiniMap.tsx
import { GoogleMap, LoadScript, Marker } from '@react-google-maps/api';

export function PropertyMiniMap({ latitude, longitude, propertyName }) {
  return (
    <div className="mini-map">
      <LoadScript googleMapsApiKey={process.env.NEXT_PUBLIC_GOOGLE_MAPS_API_KEY}>
        <GoogleMap
          mapContainerStyle={{ width: '100%', height: '300px' }}
          center={{ lat: latitude, lng: longitude }}
          zoom={15}
          options={{
            disableDefaultUI: true,  // لا أزرار — للقراءة فقط
            draggable: false,
            zoomControl: false,
            scrollwheel: false,
          }}
        >
          <Marker
            position={{ lat: latitude, lng: longitude }}
            title={propertyName}
          />
        </GoogleMap>
      </LoadScript>
    </div>
  );
}
```

### السلوك
- خريطة للقراءة فقط (لا zoom/pan/scroll)
- حجم ثابت: 300px ارتفاع (responsive width)
- النقر على الخريطة → ينتقل لـ `/map?property=ID&focus=true`

### خيارات
```jsx
<PropertyMiniMap
  latitude={24.7}
  longitude={46.7}
  propertyName="فيلا في الرياض"
  height={400}  // اختياري
  className="custom-style"
/>
```

## 2. TraderCompetitiveMap (في لوحة التاجر)

### الاستخدام
```jsx
// في TraderDashboard.tsx → تحليل السوق
import { TraderCompetitiveMap } from '@/components/TraderCompetitiveMap';

<TraderCompetitiveMap
  cityId={selectedCityId}
  propertyType={selectedType}
/>
```

### المكون
```jsx
// components/TraderCompetitiveMap.tsx
import { GoogleMap, LoadScript, Marker, MarkerClusterer } from '@react-google-maps/api';
import useSWR from 'swr';

export function TraderCompetitiveMap({ cityId, propertyType }) {
  const params = new URLSearchParams();
  if (cityId) params.set('city_id', cityId);
  if (propertyType) params.set('property_type', propertyType);

  const { data, isLoading } = useSWR(
    `/api/dashboard/trader/competitive-map?${params}`
  );

  if (isLoading) return <Spinner />;

  return (
    <LoadScript googleMapsApiKey={process.env.NEXT_PUBLIC_GOOGLE_MAPS_API_KEY}>
      <GoogleMap
        mapContainerStyle={{ width: '100%', height: '600px' }}
        center={getMapCenter(data)}
        zoom={12}
      >
        <MarkerClusterer>
          {(clusterer) => (
            <>
              {/* عقارات التاجر — Marker مميز */}
              {data.my_properties.map(p => (
                <Marker
                  key={`my-${p.id}`}
                  position={{ lat: p.latitude, lng: p.longitude }}
                  icon={{
                    url: '/icons/my-property-marker.png',
                    scaledSize: new window.google.maps.Size(40, 40),
                  }}
                  clusterer={clusterer}
                  onClick={() => showMyPropertyPopup(p)}
                />
              ))}

              {/* عقارات المنافسين — Marker عادي */}
              {data.competitor_properties.map(p => (
                <Marker
                  key={`comp-${p.id}`}
                  position={{ lat: p.latitude, lng: p.longitude }}
                  icon={{
                    url: '/icons/competitor-marker.png',
                    scaledSize: new window.google.maps.Size(30, 30),
                  }}
                  clusterer={clusterer}
                  onClick={() => showCompetitorPopup(p)}
                />
              ))}
            </>
          )}
        </MarkerClusterer>
      </GoogleMap>
    </LoadScript>
  );
}

function getMapCenter(data) {
  // مركز الخريطة = متوسط إحداثيات عقارات التاجر
  if (data?.my_properties?.length > 0) {
    const avgLat = data.my_properties.reduce((sum, p) => sum + p.latitude, 0) / data.my_properties.length;
    const avgLng = data.my_properties.reduce((sum, p) => sum + p.longitude, 0) / data.my_properties.length;
    return { lat: avgLat, lng: avgLng };
  }
  return { lat: 24.7136, lng: 46.6753 }; // الرياض افتراضياً
}
```

### Color Coding

| نوع العقار | لون Marker | Icon |
|------------|------------|------|
| عقاراتي (التاجر) | أخضر | نجمة |
| عقارات المنافسين | أحمر | دائرة |
| عقارات موثّقة (verified) | ذهبي | شارة |

### Popup
- عقاراتي: رابط لتعديل العقار
- عقارات المنافسين: رابط لعرض التفاصيل (لا يمكن التعديل)

```jsx
function showMyPropertyPopup(property) {
  // popup مع روابط: تعديل، عرض، تحليل الأداء
}

function showCompetitorPopup(property) {
  // popup مع رابط عرض فقط + مقارنة السعر
}
```

### التصفية
```jsx
const [cityId, setCityId] = useState(null);
const [propertyType, setPropertyType] = useState(null);

<Select onChange={setCityId} />
<Select onChange={setPropertyType} />
<TraderCompetitiveMap cityId={cityId} propertyType={propertyType} />
```

## 3. التكامل مع الصفحة الرئيسية للبحث

يمكن إضافة بطاقة "استكشف على الخريطة" في الصفحة الرئيسية:

```jsx
<Link href="/map" className="map-cta">
  <h3>🗺️ استكشف العقارات على الخريطة</h3>
  <p>تصفح كل العقارات بصرياً حسب موقعك الجغرافي</p>
</Link>
```

## ملاحظات

- استفد من نفس مكونات الخريطة عبر إعادة الاستخدام (Custom Hooks).
- تأكد من تجارب استخدام واضحة على الجوال (drawer للفلاتر، popup أصغر).
- اختبر بـ throttling: `npm run dev -- --host 0.0.0.0` على جوال للتجربة.
```

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Backend endpoint) | مستقل | #30 |
| 2 (Backend tests) | يعتمد على 1 | 1 |
| 3 (Frontend doc) | مستقل | #31 |

---

## معايير القبول

- [ ] `GET /api/dashboard/trader/competitive-map` يعمل للتجار فقط.
- [ ] الاستجابة تحوي `my_properties` و `competitor_properties` منفصلين.
- [ ] عقارات التاجر في الانتظار/المرفوضة لا تظهر.
- [ ] عقارات بدون إحداثيات لا تظهر.
- [ ] فلاتر city_id و property_type تعمل.
- [ ] `TraderCompetitiveMapTest` يحوي 5 tests تمر.
- [ ] `docs/frontend/map-integrations-frontend.md` موجود (200+ سطر) يشرح PropertyMiniMap + TraderCompetitiveMap.
- [ ] اختبارات جميع الخطط السابقة (25-32) لا تزال تنجح.
- [ ] `composer pint` يمر.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
