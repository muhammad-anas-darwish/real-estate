# المهمة #31: مستكشف الخريطة العام (Public Map Explorer)

> **التقرير المصدر:** `docs/reports/04-properties-map-view.md` (السيناريو 1، 2، 5، القواعد 1–11، 14–15، معايير القبول 1–6، 10–14، 16–20)
> **الهدف:** بناء صفحة `/map` الكاملة مع خريطة Google Maps تفاعلية، Clustering تلقائي، popup لكل عقار، viewport-based loading، ودعم الجوال. هذه هي الواجهة العامة للزوار.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 عالية
> **الجهد المقدّر:** 8–12 ساعة
> **الاعتمادية:** يجب أن يسبقها #30 (Geocoding + Map Data Foundation).
> **راجع:** `docs/reports/04-properties-map-view.md`

---

## الوضع الحالي

لا توجد صفحة خريطة في النظام. لا يمكن للزائر رؤية العقارات بصرياً على خريطة. الـ `MapController` من الخطة #30 يقدّم البيانات، لكن لا توجد واجهة فرونت تستهلكها.

هذه الخطة تنشئ:
- ملف `docs/frontend/map-view-frontend.md` كمرجع تفصيلي للـ Frontend developer (لأن هذه الميزة front-end heavy).
- Backend: لا شيء جديد (يكفي MapController من #30).
- تكامل: تأكيد أن `/api/map/properties` يعمل.

> **ملاحظة مهمة:** هذه الميزة Frontend-heavy. الـ Backend جاهز من الخطة #30. الخطة تركز على:
> 1. توثيق Frontend تفصيلي (مرجع الـ Frontend developer)
> 2. اختبارات integration تجريبية
> 3. أي backend adjustments طفيفة (rate limiting للـ map endpoint مثلاً)

---

## المرحلة 1: Rate Limiting للـ Map endpoint (~ 30 دقيقة)

في `App\Providers\RouteServiceProvider` (أو في `bootstrap/app.php`):

```php
RateLimiter::for('map', function (Request $request) {
    return Limit::perMinute(120)->by($request->ip());
});
```

ثم في `Modules/RealEstate/Routes/api.php`:
```php
Route::middleware('throttle:map')->get('map/properties', [MapController::class, 'properties']);
```

---

## المرحلة 2: مرجع Frontend تفصيلي (~ 3 ساعات)

`docs/frontend/map-view-frontend.md`:

```markdown
# دليل Frontend: صفحة مستكشف الخريطة

## نظرة عامة
صفحة `/map` هي صفحة public كاملة الشاشة تعرض عقارات معتمدة على Google Maps.

## التقنيات المقترحة
- React/Vue/Svelte (حسب الـ stack الحالي)
- `@react-google-maps/api` أو `@googlemaps/js-api-loader` (للخريطة)
- `@googlemaps/markerclusterer` (للـ Clustering)
- Vue Query / SWR (للـ caching على الفرونت)
- Tailwind CSS (للتصميم)

## إعداد المشروع
1. ثبّت: `npm install @googlemaps/js-api-loader @googlemaps/markerclusterer`
2. أضف في `.env.local`:
   ```
   NEXT_PUBLIC_GOOGLE_MAPS_API_KEY=your_key
   ```
3. أضف script في layout: `<script src="https://maps.googleapis.com/maps/api/js?key=...&libraries=places&v=weekly" async defer></script>`

## هيكل الصفحة
```
┌──────────────────────────────────────────────┐
│  [Header — Logo + Menu]                      │
├──────────────────────────────────────────────┤
│  [Filters Bar — collapsible]                  │
│  [Type] [Contract] [Price] [Rooms] [Area] ... │
├──────────┬───────────────────────────────────┤
│          │                                   │
│  Side    │                                   │
│  List    │        Google Map                 │
│  (30%)   │        (70%)                      │
│          │                                   │
│  - P1    │        ●  ●  ●                    │
│  - P2    │              ●                    │
│  - P3    │        [Cluster: 12]               │
│          │                                   │
└──────────┴───────────────────────────────────┘
```

## مكوّنات React المطلوبة

### 1. MapView
```jsx
// المكونات الرئيسي
<GoogleMap
  center={center}
  zoom={zoom}
  onBoundsChanged={handleBoundsChanged}  // debounced 500ms
>
  <MarkerClusterer>
    {properties.map(p => <PropertyMarker key={p.id} {...p} />)}
  </MarkerClusterer>
</GoogleMap>
```

### 2. PropertyMarker
```jsx
<Marker
  position={{ lat: p.latitude, lng: p.longitude }}
  onClick={() => setSelectedProperty(p)}
  icon={getMarkerIcon(p)}
/>
{selectedProperty?.id === p.id && (
  <InfoWindow onCloseClick={() => setSelectedProperty(null)}>
    <PropertyPopup property={p} />
  </InfoWindow>
)}
```

### 3. PropertyPopup
```jsx
<Card>
  <img src={p.main_image} />
  <h3>{p.name}</h3>
  <p>{formatPrice(p.price, p.currency)}</p>
  <p>{p.rooms} غرف • {p.area} م²</p>
  <Link href={`/properties/${p.id}`}>عرض التفاصيل</Link>
  <a href={`https://www.google.com/maps/dir/?api=1&destination=${p.latitude},${p.longitude}`}>
    الاتجاهات
  </a>
</Card>
```

### 4. FiltersBar
```jsx
<Filters>
  <Select propertyType />
  <Select contractType />
  <RangeSlider min={0} max={1M} /> {/* price */}
  <NumberInput min={1} /> {/* rooms */}
  <NumberInput min={50} /> {/* area */}
  <CitySelect />
  <Select createdWithinDays={[7, 30, 90, null]} />
  <Toggle /> {/* enable radius filter */}
  {radiusEnabled && <GeolocationButton />}
</Filters>
```

## إدارة الحالة (URL as source of truth)

استخدم `useSearchParams` للربط بين الفلاتر والـ URL:

```jsx
const [params, setParams] = useSearchParams();

const filters = useMemo(() => ({
  property_type: params.get('property_type'),
  contract_type: params.get('contract_type'),
  price_min: params.get('price_min'),
  price_max: params.get('price_max'),
  rooms_min: params.get('rooms_min'),
  area_min: params.get('area_min'),
  city_id: params.get('city_id'),
  created_within_days: params.get('created_within_days'),
  center_lat: params.get('center_lat'),
  center_lng: params.get('center_lng'),
  radius_km: params.get('radius_km'),
  sw_lat: params.get('sw_lat'),
  sw_lng: params.get('sw_lng'),
  ne_lat: params.get('ne_lat'),
  ne_lng: params.get('ne_lng'),
  focus: params.get('focus'),
  property: params.get('property'),
}), [params]);

const updateFilter = (key, value) => {
  const newParams = new URLSearchParams(params);
  if (value === null || value === '') {
    newParams.delete(key);
  } else {
    newParams.set(key, value);
  }
  setParams(newParams);
};
```

## Fetching Logic

```jsx
const { data, isLoading } = useSWR(
  ['/api/map/properties', filters],
  fetcher,
  {
    revalidateOnFocus: false,
    dedupingInterval: 30000,  // 30s
  }
);
```

## Clustering

```jsx
import { MarkerClusterer } from '@googlemaps/markerclusterer';

<MarkerClusterer>
  {(clusterer) => 
    properties.map(p => (
      <PropertyMarker 
        key={p.id} 
        property={p} 
        clusterer={clusterer}
      />
    ))
  }
</MarkerClusterer>
```

خيارات:
- `gridSize: 60` — حجم شبكة التجميع
- `maxZoom: 15` — أعلى zoom للتجميع
- `minimumClusterSize: 2` — أقل عدد للتجميع

## Geolocation

```jsx
const requestLocation = () => {
  if (!navigator.geolocation) {
    alert('المتصفح لا يدعم تحديد الموقع');
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      updateFilter('center_lat', pos.coords.latitude);
      updateFilter('center_lng', pos.coords.longitude);
      updateFilter('radius_km', 5);  // افتراضي 5 كم
    },
    (err) => {
      // المستخدم رفض — استخدم موقع افتراضي
      console.warn('Geolocation denied:', err);
    }
  );
};
```

## Mobile (Responsive)

- Desktop: side list (30%) + map (70%)
- Mobile: full-screen map + drawer للفلاتر + bottom sheet للقائمة
- استخدم `useMediaQuery` لكشف حجم الشاشة

## الأداء

1. **debounce** على `onBoundsChanged` (500ms)
2. **viewport-based loading**: عند تحريك الخريطة، أرسل bounds الجديد → يجلب عقارات في النطاق فقط
3. **marker recycling**: استخدم `MarkerClusterer` (يديرها تلقائياً)
4. **lazy load الصور** في popup
5. **virtual scrolling** للقائمة الجانبية (إذا > 50)

## رسائل المستخدم

- "يتم عرض 500 من 1500 عقار. كبّر الخريطة أو طبّق فلاتر أضيق."
- "لا توجد عقارات في هذا النطاق. وسّع البحث."
- "تعذّر تحميل الخريطة. يرجى التحقق من الاتصال."

## Deep Link

`/map?property=123&focus=true` — يفتح الخريطة مركزّة على العقار 123 مع popup مفتوح.

```jsx
useEffect(() => {
  if (filters.focus && filters.property) {
    fetchProperty(filters.property).then(p => {
      setSelectedProperty(p);
      map.panTo({ lat: p.latitude, lng: p.longitude });
      map.setZoom(15);
    });
  }
}, [filters.focus, filters.property]);
```

## الاختبار اليدوي (Acceptance)

- [ ] افتح `/map` → تظهر الخريطة مع علامات
- [ ] انقر على cluster → الخريطة تكبّر
- [ ] انقر على علامة → popup بمعلومات العقار
- [ ] طبّق فلتر "شقة" → العلامات تتحدث
- [ ] انقر "الاتجاهات" → يفتح Google Maps
- [ ] انقر "عرض التفاصيل" → ينقل لصفحة العقار
- [ ] فعّل Geolocation → الدائرة الحمراء تظهر
- [ ] افتح `/map?property=1&focus=true` → الخريطة مركّزة على العقار
- [ ] انسخ الـ URL وافتحه في تبويب جديد → نفس النتائج
- [ ] صغّر النافذة للموبايل → drawer الفلاتر يظهر
```

---

## المرحلة 3: اختبارات Backend المتبقية (~ 2 ساعة)

### 3.1 `MapExplorerIntegrationTest`

`Modules/RealEstate/Tests/MapExplorerIntegrationTest.php`:

```php
<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;
use Tests\TestCase;

class MapExplorerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_response_includes_required_fields_for_frontend(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7,
            'longitude' => 46.7,
            'price' => 250000,
            'currency' => 'SAR',
            'rooms' => 3,
            'area' => 150,
        ]);

        $response = $this->getJson('/api/map/properties');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'properties' => [
                    '*' => [
                        'id', 'name', 'latitude', 'longitude',
                        'price', 'currency', 'rooms', 'area',
                        'main_image', 'city_id', 'city_name',
                    ],
                ],
                'meta' => ['total_matching', 'returned', 'is_truncated', 'limit'],
                'filter',
            ],
        ]);
    }

    public function test_filter_combinations(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'property_type' => PropertyType::APARTMENT,
            'type_of_contract' => TypeOfContract::SALE,
            'price' => 200000, 'rooms' => 2,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.8, 'longitude' => 46.8,
            'property_type' => PropertyType::VILLA,
            'type_of_contract' => TypeOfContract::RENT,
            'price' => 800000, 'rooms' => 5,
        ]);

        $response = $this->getJson(
            '/api/map/properties?property_type=apartment&contract_type=sale&price_max=300000&rooms_min=2'
        );

        $this->assertCount(1, $response->json('data.properties'));
    }

    public function test_bounds_filter(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 30.0, 'longitude' => 50.0,  // خارج النطاق
        ]);

        $response = $this->getJson(
            '/api/map/properties?sw_lat=24.0&sw_lng=46.0&ne_lat=25.0&ne_lng=47.0'
        );

        $this->assertCount(1, $response->json('data.properties'));
    }

    public function test_radius_filter(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 30.0, 'longitude' => 50.0,  // ~700 km away
        ]);

        $response = $this->getJson(
            '/api/map/properties?center_lat=24.7&center_lng=46.7&radius_km=50'
        );

        $this->assertCount(1, $response->json('data.properties'));
    }
}
```

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Rate Limiting) | مستقل | #30 |
| 2 (Frontend doc) | مستقل | #30 |
| 3 (Backend tests) | مستقل | #30 |

> **ملاحظة:** هذه الخطة لا تحتاج Frontend implementation code (الـ team الفرونت منفصل). نركّز على التوثيق + اختبارات الـ Backend.

---

## معايير القبول

- [ ] Route `/api/map/properties` محدود بـ 120 requests/minute/IP.
- [ ] `docs/frontend/map-view-frontend.md` موجود (300+ سطر، يشرح كل تفصيلة).
- [ ] استجابة الـ API تحوي الحقول التي يحتاجها الفرونت (id, name, lat, lng, price, currency, rooms, area, main_image, city_name).
- [ ] فلاتر combination تعمل (multiple filters في نفس الطلب).
- [ ] Bounds filter يعمل (sw/ne).
- [ ] Radius filter يعمل (center + radius_km).
- [ ] اختبارات `MapExplorerIntegrationTest` (4 tests) تمر.
- [ ] اختبارات `MapControllerTest` (من #30) لا تزال تنجح.
- [ ] `composer pint` يمر.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
