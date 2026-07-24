# دليل Frontend: صفحة مستكشف الخريطة (Map View)

> **الإصدار:** 1.0
> **التاريخ:** 2026-07-24
> **الـ backend:** `Modules/RealEstate/Http/Controllers/MapController.php`

## نظرة عامة
صفحة `/map` هي صفحة public كاملة الشاشة تعرض عقارات معتمدة على Google Maps.

## التقنيات المقترحة
- React/Vue/Svelte (حسب الـ stack الحالي)
- `@googlemaps/js-api-loader` (للخريطة)
- `@googlemaps/markerclusterer` (للـ Clustering)
- SWR / Vue Query (للـ caching على الفرونت)
- Tailwind CSS (للتصميم)

## إعداد المشروع
1. `npm install @googlemaps/js-api-loader @googlemaps/markerclusterer`
2. في `.env.local`:
   ```
   NEXT_PUBLIC_GOOGLE_MAPS_API_KEY=your_key_here
   ```
3. تحميل Google Maps API: `<script src="https://maps.googleapis.com/maps/api/js?key=...&libraries=places&v=weekly" async defer></script>`

## Backend API
- `GET /api/map/properties` (public, rate-limited 120/min)
- Query params: `property_type`, `contract_type`, `price_min`, `price_max`, `rooms_min`, `area_min`, `city_id`, `country_id`, `publisher_type`, `created_within_days`, `center_lat`, `center_lng`, `radius_km`, `sw_lat`, `sw_lng`, `ne_lat`, `ne_lng`, `limit`
- Response: `{ success, data: { properties: [...], meta: { total_matching, returned, is_truncated, limit, truncation_message }, filter: {...} } }`

## هيكل الصفحة
```
┌──────────────────────────────────────────────┐
│  [Header — Logo + Menu]                      │
├──────────────────────────────────────────────┤
│  [Filters Bar — collapsible]                  │
├──────────┬───────────────────────────────────┤
│ Side     │                                    │
│ List     │        Google Map                 │
│ (30%)    │        (70%)                       │
│          │                                    │
│ - P1     │        ●  ●  ●                    │
│ - P2     │              ●                    │
│ - P3     │        [Cluster: 12]               │
└──────────┴───────────────────────────────────┘
```

## مكوّنات React المطلوبة

### 1. MapView
```jsx
import { GoogleMap, useJsApiLoader } from '@react-google-maps/api';
import { useEffect, useState } from 'react';

export function MapView({ center, zoom, onBoundsChanged }) {
  const { isLoaded } = useJsApiLoader({
    googleMapsApiKey: process.env.NEXT_PUBLIC_GOOGLE_MAPS_API_KEY,
    libraries: ['places'],
  });

  if (!isLoaded) return <Spinner />;

  return (
    <GoogleMap
      center={center}
      zoom={zoom}
      onIdle={() => onBoundsChanged?.(map.getBounds())}
      mapContainerStyle={{ width: '100%', height: '100%' }}
    >
      {/* markers */}
    </GoogleMap>
  );
}
```

### 2. MarkerClusterer
```jsx
import { MarkerClusterer } from '@googlemaps/markerclusterer';

<MarkerClusterer options={{ gridSize: 60, maxZoom: 15 }}>
  {(clusterer) =>
    properties.map((p) => (
      <PropertyMarker key={p.id} property={p} clusterer={clusterer} />
    ))
  }
</MarkerClusterer>
```

### 3. PropertyMarker + InfoWindow
```jsx
function PropertyMarker({ property, clusterer }) {
  const [showInfo, setShowInfo] = useState(false);

  return (
    <>
      <Marker
        position={{ lat: property.latitude, lng: property.longitude }}
        clusterer={clusterer}
        onClick={() => setShowInfo(true)}
      />
      {showInfo && (
        <InfoWindow
          position={{ lat: property.latitude, lng: property.longitude }}
          onCloseClick={() => setShowInfo(false)}
        >
          <PropertyPopup property={property} />
        </InfoWindow>
      )}
    </>
  );
}
```

### 4. PropertyPopup
```jsx
function PropertyPopup({ property }) {
  return (
    <div className="popup">
      <img src={property.main_image} alt={property.name} loading="lazy" />
      <h3>{property.name}</h3>
      <p className="price">{formatPrice(property.price, property.currency)}</p>
      <p className="details">
        {property.rooms} غرف • {property.area} م²
      </p>
      <div className="actions">
        <a href={`/properties/${property.id}`} className="btn-primary">
          عرض التفاصيل
        </a>
        <a
          href={`https://www.google.com/maps/dir/?api=1&destination=${property.latitude},${property.longitude}`}
          target="_blank"
          rel="noopener"
        >
          الاتجاهات
        </a>
      </div>
    </div>
  );
}
```

## URL State Sync (مهم للـ Deep linking)

```jsx
import { useSearchParams } from 'next/navigation';
import { useMemo } from 'react';

function useMapFilters() {
  const [params, setParams] = useSearchParams();

  const filters = useMemo(() => ({
    property_type: params.get('property_type'),
    contract_type: params.get('contract_type'),
    price_min: numOrNull(params.get('price_min')),
    price_max: numOrNull(params.get('price_max')),
    rooms_min: numOrNull(params.get('rooms_min')),
    area_min: numOrNull(params.get('area_min')),
    city_id: numOrNull(params.get('city_id')),
    publisher_type: numOrNull(params.get('publisher_type')),
    created_within_days: numOrNull(params.get('created_within_days')),
    center_lat: numOrNull(params.get('center_lat')),
    center_lng: numOrNull(params.get('center_lng')),
    radius_km: numOrNull(params.get('radius_km')),
    sw_lat: numOrNull(params.get('sw_lat')),
    sw_lng: numOrNull(params.get('sw_lng')),
    ne_lat: numOrNull(params.get('ne_lat')),
    ne_lng: numOrNull(params.get('ne_lng')),
    focus: params.get('focus'),
    property: params.get('property'),
  }), [params]);

  const update = (key, value) => {
    const next = new URLSearchParams(params);
    if (value === null || value === '' || value === false) {
      next.delete(key);
    } else {
      next.set(key, String(value));
    }
    setParams(next);
  };

  return { filters, update };
}
```

## Fetching

```jsx
import useSWR from 'swr';

const fetcher = (url) => fetch(url).then((r) => r.json());

function useMapProperties(filters) {
  const params = new URLSearchParams();
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== null && v !== undefined) params.set(k, v);
  });

  const { data, error, isLoading } = useSWR(
    [`/api/map/properties?${params}`],
    fetcher,
    {
      revalidateOnFocus: false,
      dedupingInterval: 30000,
    }
  );

  return {
    properties: data?.data?.properties ?? [],
    meta: data?.data?.meta,
    isLoading,
    error,
  };
}
```

## Geolocation

```jsx
async function requestLocation() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation not supported'));
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
      (err) => reject(err),
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
  });
}
```

## Mobile Responsive

- **Desktop:** side list (30%) + map (70%)
- **Mobile:** full-screen map + drawer للفلاتر + bottom sheet للقائمة
- استخدم `useMediaQuery` لكشف حجم الشاشة

## الأداء

1. **debounce 500ms** على `onIdle` callback
2. **viewport-based loading**: عند تحريك الخريطة، أرسل bounds الجديد
3. **marker recycling**: استخدم `MarkerClusterer`
4. **lazy load** الصور في popup
5. **virtual scrolling** للقائمة الجانبية (إذا > 50)

## Deep Link

`/map?property=123&focus=true` — يفتح الخريطة مركّزة على العقار 123:

```jsx
useEffect(() => {
  if (filters.focus && filters.property) {
    fetch(`/api/properties/${filters.property}/details`)
      .then(r => r.json())
      .then(({ data }) => {
        map.panTo({ lat: data.latitude, lng: data.longitude });
        map.setZoom(15);
        setSelectedProperty(data);
      });
  }
}, [filters.focus, filters.property]);
```

## رسائل المستخدم

| الحالة | الرسالة |
|--------|---------|
| نتائج كثيرة | "يتم عرض 500 من 1500 عقار. كبّر الخريطة أو طبّق فلاتر أضيق." |
| لا نتائج | "لا توجد عقارات في هذا النطاق. وسّع البحث." |
| لا إنترنت | "تعذّر تحميل الخريطة. يرجى التحقق من الاتصال." |

## Acceptance Checklist

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
