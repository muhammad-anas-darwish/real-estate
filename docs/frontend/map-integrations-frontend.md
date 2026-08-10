# دليل Frontend: PropertyMiniMap + TraderCompetitiveMap

> **الإصدار:** 1.0
> **التاريخ:** 2026-07-24

## 1. PropertyMiniMap (في صفحة تفاصيل العقار)

### الاستخدام
```jsx
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
import { GoogleMap, useJsApiLoader, Marker } from '@react-google-maps/api';

export function PropertyMiniMap({ latitude, longitude, propertyName, height = 300 }) {
  const { isLoaded } = useJsApiLoader({
    googleMapsApiKey: process.env.NEXT_PUBLIC_GOOGLE_MAPS_API_KEY,
  });

  if (!isLoaded) return <div className="map-skeleton" style={{ height }} />;
  if (!latitude || !longitude) {
    return (
      <div className="map-empty" style={{ height }}>
        لا تتوفر إحداثيات لهذا العقار.
      </div>
    );
  }

  return (
    <GoogleMap
      mapContainerStyle={{ width: '100%', height: `${height}px` }}
      center={{ lat: latitude, lng: longitude }}
      zoom={15}
      options={{
        disableDefaultUI: true,
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
  );
}
```

### السلوك
- خريطة للقراءة فقط (لا zoom/pan/scroll)
- حجم افتراضي: 300px ارتفاع (responsive width)
- إذا لم تتوفر إحداثيات: رسالة "لا تتوفر إحداثيات"

### Props
```jsx
<PropertyMiniMap
  latitude={24.7}
  longitude={46.7}
  propertyName="فيلا في الرياض"
  height={400}
  className="custom-style"
/>
```

## 2. TraderCompetitiveMap (في لوحة التاجر)

### الاستخدام
```jsx
import { TraderCompetitiveMap } from '@/components/TraderCompetitiveMap';

<TraderCompetitiveMap
  cityId={selectedCityId}
  propertyType={selectedType}
/  onPropertyClick={(p) => setSelectedProperty(p)}
/>
```

### المكون
```jsx
import { GoogleMap, useJsApiLoader, Marker, MarkerClusterer, InfoWindow } from '@react-google-maps/api';
import useSWR from 'swr';

const myMarkerIcon = '/icons/my-property-marker.png';
const competitorMarkerIcon = '/icons/competitor-marker.png';

export function TraderCompetitiveMap({ cityId, propertyType }) {
  const { isLoaded } = useJsApiLoader({
    googleMapsApiKey: process.env.NEXT_PUBLIC_GOOGLE_MAPS_API_KEY,
  });

  const params = new URLSearchParams();
  if (cityId) params.set('city_id', cityId);
  if (propertyType) params.set('property_type', propertyType);

  const { data, isLoading, error } = useSWR(
    `/api/dashboard/trader/competitive-map?${params}`
  );

  if (!isLoaded || isLoading) return <Spinner />;
  if (error) return <div>حدث خطأ في تحميل الخريطة</div>;

  const myProps = data?.data?.my_properties ?? [];
  const compProps = data?.data?.competitor_properties ?? [];
  const center = computeCenter(myProps);

  return (
    <GoogleMap
      mapContainerStyle={{ width: '100%', height: '600px' }}
      center={center}
      zoom={12}
    >
      <MarkerClusterer>
        {(clusterer) => (
          <>
            {myProps.map((p) => (
              <Marker
                key={`my-${p.id}`}
                position={{ lat: p.latitude, lng: p.longitude }}
                icon={myMarkerIcon}
                clusterer={clusterer}
                onClick={() => showMyPropertyPopup(p)}
              />
            ))}

            {compProps.map((p) => (
              <Marker
                key={`comp-${p.id}`}
                position={{ lat: p.latitude, lng: p.longitude }}
                icon={competitorMarkerIcon}
                clusterer={clusterer}
                onClick={() => showCompetitorPopup(p)}
              />
            ))}
          </>
        )}
      </MarkerClusterer>
    </GoogleMap>
  );
}

function computeCenter(properties) {
  if (properties.length === 0) {
    return { lat: 24.7136, lng: 46.6753 };
  }
  const avgLat = properties.reduce((sum, p) => sum + p.latitude, 0) / properties.length;
  const avgLng = properties.reduce((sum, p) => sum + p.longitude, 0) / properties.length;
  return { lat: avgLat, lng: avgLng };
}
```

### Color Coding

| نوع العقار | لون | Icon |
|------------|-----|------|
| عقاراتي (التاجر) | أخضر | نجمة ⭐ |
| عقارات المنافسين | أحمر | دائرة 🔴 |
| عقارات موثّقة (verified) | ذهبي | شارة ✓ |

### Popup
- **عقاراتي:** روابط: تعديل، عرض، تحليل الأداء
- **عقارات المنافسين:** رابط عرض فقط + مقارنة السعر

### التصفية
```jsx
const [cityId, setCityId] = useState(null);
const [propertyType, setPropertyType] = useState(null);

<Select onChange={setCityId} placeholder="كل المدن" />
<Select onChange={setPropertyType} placeholder="كل الأنواع" />
<TraderCompetitiveMap cityId={cityId} propertyType={propertyType} />
```

## 3. التكامل مع الصفحة الرئيسية للبحث

```jsx
<Link href="/map" className="map-cta">
  <h3>🗺️ استكشف العقارات على الخريطة</h3>
  <p>تصفح كل العقارات بصرياً حسب موقعك الجغرافي</p>
</Link>
```

## 4. ملاحظات

- استفد من نفس مكونات الخريطة عبر Custom Hooks (`useMap`).
- اختبر على الجوال بـ throttling: DevTools → Network → Slow 3G.
- استخدم `react-window` للقوائم الطويلة (> 50 عقار).
