# دليل Frontend: الفلاتر المتقدمة + Geolocation + Deep Linking

> **الإصدار:** 1.0
> **التاريخ:** 2026-07-24

## مكون FiltersBar

```jsx
// components/MapFilters.tsx
import { useState, useMemo } from 'react';
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

  const reset = () => setParams(new URLSearchParams());

  return (
    <div className="filters-bar">
      <Select
        label="نوع العقار"
        value={filters.property_type}
        options={['apartment', 'house', 'villa', 'land', 'commercial', 'office', 'warehouse']}
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

      <button onClick={reset}>إعادة الضبط</button>
    </div>
  );
}
```

## Geolocation Flow

```javascript
async function requestGeolocation() {
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
      (err) => reject(err),
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000,
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
    publisher_type: numberOrNull(params.get('publisher_type')),
    created_within_days: numberOrNull(params.get('created_within_days')),
    center_lat: numberOrNull(params.get('center_lat')),
    center_lng: numberOrNull(params.get('center_lng')),
    radius_km: numberOrNull(params.get('radius_km')),
    sw_lat: numberOrNull(params.get('sw_lat')),
    sw_lng: numberOrNull(params.get('sw_lng')),
    ne_lat: numberOrNull(params.get('ne_lat')),
    ne_lng: numberOrNull(params.get('ne_lng')),
    focus: params.get('focus'),
    property: params.get('property'),
  };
}
```

## Empty State

```jsx
{data?.meta.total_matching === 0 && (
  <div className="empty-state">
    <h3>لا توجد عقارات مطابقة</h3>
    <p>جرّب توسيع الفلاتر أو إزالة بعضها.</p>
    <button onClick={reset}>إعادة ضبط الفلاتر</button>
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

## Mobile Drawer

```jsx
const isMobile = useMediaQuery('(max-width: 768px)');

{isMobile ? (
  <Drawer open={isOpen} onClose={() => setIsOpen(false)} side="right">
    <MapFilters />
  </Drawer>
) : (
  <aside className="sidebar">
    <MapFilters />
  </aside>
)}
```

## Acceptance Checklist

- [ ] 8 فلاتر تعمل وتنعكس على الـ URL
- [ ] Geolocation يطلب الإذن مرة واحدة فقط
- [ ] نصف القطر slider يعمل (1-50 كم)
- [ ] Deep link يفتح نفس النتائج
- [ ] Empty state يظهر عند 0 نتائج
- [ ] Truncation warning يظهر عند 500+
- [ ] Mobile drawer يعمل
