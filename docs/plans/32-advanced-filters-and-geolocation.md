# المهمة #32: فلاتر متقدمة + Geolocation + Deep Linking

> **التقرير المصدر:** `docs/reports/04-properties-map-view.md` (السيناريوهات 2، 5، القواعد 7، 8، معايير القبول 7–13)
> **الهدف:** إضافة ميزات الفلاتر المتقدمة على الـ Frontend (8 فلاتر + Geolocation + URL sync) + معالجة الـ edge cases على الـ Backend.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟡 متوسطة
> **الجهد المقدّر:** 6–8 ساعات
> **الاعتمادية:** يجب أن يسبقها #31 (Map Explorer).
> **راجع:** `docs/reports/04-properties-map-view.md`

> **ملاحظة:** هذه الخطة Frontend-heavy مثل #31. تركّز على:
> 1. Backend: اختبارات لـ edge cases (0 results, no coordinates, etc.)
> 2. Frontend: توثيق تفصيلي لمكون FiltersBar + Geolocation + Deep linking

---

## الوضع الحالي

بعد الخطة #31، الـ Backend يدعم كل الفلاتر (8 types + bounds + radius). لكن لا يوجد:
- Backend: معالجة الـ edge cases (مثل عقارات بدون إحداثيات، نتائج 0).
- Frontend: مكون FiltersBar كامل + Geolocation + URL sync.

هذه الخطة:
- Backend: `MapFilterService` يطبّق قواعد العمل و edge cases.
- Frontend: توثيق تفصيلي في `docs/frontend/map-filters-frontend.md`.

---

## المرحلة 1: Backend - `MapFilterService` (~ 2 ساعة)

`Modules/RealEstate/Services/MapFilterService.php`:

```php
<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\ValueObjects\MapBounds;

class MapFilterService extends BaseService
{
    /**
     * حساب عدد العقارات داخل bounding box (سريع — يستخدم في الـ frontend لتحذير المستخدم).
     */
    public function countInBounds(MapBounds $bounds, ?int $cityId = null): int
    {
        $query = Property::query()
            ->where('status', PropertyStatus::APPROVED)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$bounds->swLat, $bounds->neLat])
            ->whereBetween('longitude', [$bounds->swLng, $bounds->neLng]);

        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        return $query->count();
    }

    /**
     * اقتراح bounding box "أوسع" عند تجاوز الحد 500.
     */
    public function suggestBroaderBounds(MapBounds $bounds, int $currentCount, int $limit = 500): ?MapBounds
    {
        if ($currentCount <= $limit) {
            return null;
        }

        // وسّع بنسبة 50%
        $latRange = $bounds->neLat - $bounds->swLat;
        $lngRange = $bounds->neLng - $bounds->swLng;

        $expand = 0.25;  // 25% من كل جانب

        return new MapBounds(
            swLat: $bounds->swLat - $latRange * $expand,
            swLng: $bounds->swLng - $lngRange * $expand,
            neLat: $bounds->neLat + $latRange * $expand,
            neLng: $bounds->neLng + $lngRange * $expand,
        );
    }
}
```

---

## المرحلة 2: Backend tests للـ edge cases (~ 2 ساعة)

`Modules/RealEstate/Tests/MapEdgeCasesTest.php`:

```php
<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Services\MapFilterService;
use Modules\RealEstate\ValueObjects\MapBounds;
use Tests\TestCase;

class MapEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_empty_database_returns_zero(): void
    {
        $response = $this->getJson('/api/map/properties');
        $this->assertCount(0, $response->json('data.properties'));
        $this->assertEquals(0, $response->json('data.meta.total_matching'));
        $this->assertFalse($response->json('data.meta.is_truncated'));
    }

    public function test_all_properties_without_coordinates_excluded(): void
    {
        Property::factory()->count(5)->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => null, 'longitude' => null,
        ]);

        $response = $this->getJson('/api/map/properties');
        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_filter_returns_zero_when_no_match(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
            'price' => 100000,
        ]);

        $response = $this->getJson('/api/map/properties?price_min=500000');
        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_radius_zero_returns_nothing(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);

        // radius 0.001 km = 1 meter — likely not enough
        $response = $this->getJson(
            '/api/map/properties?center_lat=24.7&center_lng=46.7&radius_km=0.001'
        );
        $this->assertCount(0, $response->json('data.properties'));
    }

    public function test_count_in_bounds_helper(): void
    {
        $bounds = new MapBounds(24.0, 46.0, 25.0, 47.0);

        Property::factory()->count(3)->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 24.5, 'longitude' => 46.5,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'latitude' => 30.0, 'longitude' => 50.0,
        ]);

        $service = new MapFilterService();
        $count = $service->countInBounds($bounds);
        $this->assertEquals(3, $count);
    }

    public function test_suggest_broader_bounds_when_truncated(): void
    {
        $bounds = new MapBounds(24.0, 46.0, 25.0, 47.0);
        $service = new MapFilterService();

        // No truncation → null
        $this->assertNull($service->suggestBroaderBounds($bounds, 100));

        // Truncated → broader bounds
        $broader = $service->suggestBroaderBounds($bounds, 600);
        $this->assertNotNull($broader);
        $this->assertLessThan($bounds->swLat, $broader->swLat);
        $this->assertGreaterThan($bounds->neLat, $broader->neLat);
    }
}
```

---

## المرحلة 3: Frontend - مرجع FiltersBar + Geolocation (~ 2 ساعة)

`docs/frontend/map-filters-frontend.md`:

```markdown
# دليل Frontend: الفلاتر المتقدمة + Geolocation

## مكون FiltersBar

```jsx
// components/MapFilters.tsx
import { useState, useEffect } from 'react';
import { useSearchParams } from 'next/navigation';

export function MapFilters({ onChange }) {
  const [params, setParams] = useSearchParams();
  const [radiusEnabled, setRadiusEnabled] = useState(
    Boolean(params.get('radius_km'))
  );

  const filters = useMemo(() => parseFilters(params), [params]);

  const update = (key, value) => {
    const next = new URLSearchParams(params);
    if (value === null || value === '' || value === false) {
      next.delete(key);
    } else {
      next.set(key, String(value));
    }
    setParams(next);
    onChange?.(next);
  };

  return (
    <div className="filters-bar">
      <Select
        label="نوع العقار"
        value={filters.property_type}
        options={['apartment', 'villa', 'land', 'commercial']}
        onChange={(v) => update('property_type', v)}
      />

      <Select
        label="نوع العقد"
        value={filters.contract_type}
        options={['sale', 'rent']}
        onChange={(v) => update('contract_type', v)}
      />

      <RangeSlider
        label="السعر"
        min={filters.price_min ?? 0}
        max={filters.price_max ?? 1_000_000}
        onChange={([min, max]) => {
          update('price_min', min);
          update('price_max', max);
        }}
      />

      <NumberInput
        label="عدد الغرف (الحد الأدنى)"
        value={filters.rooms_min}
        onChange={(v) => update('rooms_min', v)}
      />

      <NumberInput
        label="المساحة (الحد الأدنى)"
        value={filters.area_min}
        onChange={(v) => update('area_min', v)}
      />

      <CitySelect
        value={filters.city_id}
        onChange={(v) => update('city_id', v)}
      />

      <Select
        label="تاريخ النشر"
        value={filters.created_within_days}
        options={[
          { value: 7, label: 'آخر 7 أيام' },
          { value: 30, label: 'آخر 30 يوم' },
          { value: 90, label: 'آخر 90 يوم' },
        ]}
        onChange={(v) => update('created_within_days', v)}
      />

      <Select
        label="نوع الناشر"
        value={filters.publisher_type}
        options={['individual', 'office']}
        onChange={(v) => update('publisher_type', v)}
      />

      <div className="radius-filter">
        <label>
          <input
            type="checkbox"
            checked={radiusEnabled}
            onChange={(e) => {
              setRadiusEnabled(e.target.checked);
              if (!e.target.checked) {
                update('radius_km', null);
                update('center_lat', null);
                update('center_lng', null);
              }
            }}
          />
          ابحث ضمن نصف قطر من موقعي
        </label>

        {radiusEnabled && (
          <div>
            <button onClick={requestGeolocation}>
              📍 استخدم موقعي الحالي
            </button>
            <input
              type="range"
              min={1} max={50} step={1}
              value={filters.radius_km ?? 5}
              onChange={(e) => update('radius_km', e.target.value)}
            />
            <span>{filters.radius_km ?? 5} كم</span>
          </div>
        )}
      </div>

      <button onClick={resetFilters}>إعادة الضبط</button>
    </div>
  );
}
```

## Geolocation Flow

```javascript
function requestGeolocation() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation not supported'));
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        resolve({
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
        });
      },
      (err) => {
        reject(err);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000,  // cache 1 min
      }
    );
  });
}

// Usage
try {
  const { lat, lng } = await requestGeolocation();
  update('center_lat', lat);
  update('center_lng', lng);
  update('radius_km', 5);
} catch (err) {
  if (err.code === err.PERMISSION_DENIED) {
    toast.info('لم نتمكن من الوصول لموقعك. نستخدم موقع افتراضي.');
  }
}
```

## URL State Sync

استخدم `useSearchParams` + `useRouter` (Next.js) أو `useNavigate` (React Router) للحفاظ على URL متزامن.

```jsx
function parseFilters(params) {
  return {
    property_type: params.get('property_type'),
    contract_type: params.get('contract_type'),
    price_min: numberOrNull(params.get('price_min')),
    price_max: numberOrNull(params.get('price_max')),
    rooms_min: numberOrNull(params.get('rooms_min')),
    area_min: numberOrNull(params.get('area_min')),
    city_id: numberOrNull(params.get('city_id')),
    publisher_type: params.get('publisher_type'),
    created_within_days: numberOrNull(params.get('created_within_days')),
    center_lat: numberOrNull(params.get('center_lat')),
    center_lng: numberOrNull(params.get('center_lng')),
    radius_km: numberOrNull(params.get('radius_km')),
    focus: params.get('focus'),
    property: params.get('property'),
  };
}
```

## Reset Filters

```jsx
function resetFilters() {
  setParams(new URLSearchParams());
}
```

## Mobile Filters Drawer

```jsx
// استخدم drawer للشاشات الصغيرة
<Drawer open={isOpen} onClose={() => setIsOpen(false)} side="right">
  <MapFilters />
</Drawer>
```

## Empty State

```jsx
{data?.meta.total_matching === 0 && (
  <div className="empty-state">
    <h3>لا توجد عقارات مطابقة</h3>
    <p>جرّب توسيع الفلاتر أو إزالة بعضها.</p>
    <button onClick={resetFilters}>إعادة ضبط الفلاتر</button>
  </div>
)}
```

## Truncation Warning

```jsx
{data?.meta.is_truncated && (
  <div className="truncation-warning">
    ⚠️ {data.meta.truncation_message}
  </div>
)}
```
```

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (MapFilterService) | مستقل | #30 |
| 2 (Edge cases tests) | مستقل | #30 + 1 |
| 3 (Frontend doc) | مستقل | #31 |

---

## معايير القبول

- [ ] `MapFilterService` يحوي `countInBounds()` و `suggestBroaderBounds()`.
- [ ] `MapEdgeCasesTest` يحوي 6 tests تمر.
- [ ] Empty database يعرض 0 عقارات.
- [ ] عقارات بدون إحداثيات لا تكسر الخريطة.
- [ ] فلتر بنطاق 0 (radius_km=0.001) يعيد 0 عقارات.
- [ ] `docs/frontend/map-filters-frontend.md` موجود (200+ سطر) يشرح FiltersBar + Geolocation + URL sync.
- [ ] اختبارات جميع الخطط السابقة (25–31) لا تزال تنجح.
- [ ] `composer pint` يمر.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
