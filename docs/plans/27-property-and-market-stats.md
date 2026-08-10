# المهمة #27: إحصائيات العقار الواحد وإحصائيات السوق

> **التقرير المصدر:** `docs/ideas/statistics-and-dashboards/report.md` (السطور 60–95 "إحصائيات عقار واحد" + "إحصائيات السوق"، السيناريوهات 2 و 3، معايير القبول "إحصائيات عقار واحد" + "إحصائيات السوق")
> **الهدف:** بناء صفحتي إحصائيات: (أ) صفحة إحصائيات تفصيلية لعقار واحد (auth)، (ب) صفحة إحصائيات السوق العامة للزائرين (public). كلاهما يستهلكان نفس `PropertyStatsService` (للعقار) و `MarketStatsService` (للسوق).
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟡 متوسطة
> **الجهد المقدّر:** 6–9 ساعات
> **الاعتمادية:** يجب أن يسبقها #25 (Statistics Foundation).
> **راجع:** `docs/ideas/statistics-and-dashboards/report.md` (السطور 60–95، 170–175، 245–250)

---

## الوضع الحالي

لا توجد صفحات إحصائيات:
- الزائر لا يستطيع رؤية إجمالي عدد العقارات، أو متوسط الأسعار في مدينة، أو أكثر 10 عقارات مشاهدة.
- التاجر/الإدمن لا يستطيع الدخول على صفحة عقار ورؤية: قمع التحويل (view → save → contact → appointment)، سجل تغيير السعر، مصادر الزيارات.

هذه الخطة تنشئ:
- `PropertyStatsService` و `PropertyStatsController` (تحت `/api/dashboard/properties/{property}/stats`).
- `MarketStatsService` و `MarketStatsController` (تحت `/api/market/*` بدون auth).
- 7 endpoints للسوق + 1 endpoint للعقار.

> **ملاحظة:** الـ `PropertyStatsService`, `PropertyStatsController`, `MarketStatsService`, `MarketStatsController` stubs من الخطة #25 تُملأ هنا.

---

## المرحلة 1: `PropertyStatsService` (~ 3 ساعات)

`Modules/Statistics/Services/PropertyStatsService.php` (يحلّ محل الـ stub):

```php
<?php

namespace Modules\Statistics\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;
use Modules\RealEstate\Entities\AdVisit;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Entities\Review;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\ViewingStatus;
use Modules\Statistics\DTOs\StatsFilterDTO;
use Modules\Statistics\Enums\StatsPeriod;

class PropertyStatsService extends BaseService
{
    public const CACHE_TTL_SECONDS = 300;

    public const CACHE_TAG = 'property_stats';

    public function __construct(
        protected readonly StatsCacheHelper $cache
    ) {}

    public function show(int $propertyId, StatsFilterDTO $filter): array
    {
        $property = Property::with(['country', 'city', 'publisher'])->findOrFail($propertyId);

        return $this->cache->remember(
            'property',
            'show',
            ['property' => $propertyId, 'f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            function () use ($property, $filter) {
                $days = $filter->days();
                $from = $filter->range->from;
                $to = $filter->range->to;

                // 1) KPIs
                $totalViews = PropertyView::where('property_id', $property->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->count();

                $uniqueViewers = PropertyView::where('property_id', $property->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('user_id')
                    ->distinct('user_id')
                    ->count('user_id');

                $favoritesCount = $property->favoritedBy()->count();

                $leadsFromProperty = Lead::where('trader_id', $property->publisher_id)
                    ->where('source', 'property:'.$property->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->count();

                $appointments = Appointment::where('property_id', $property->id)
                    ->whereBetween('scheduled_at', [$from, $to])
                    ->count();

                $averageRating = Review::where('property_id', $property->id)->avg('rating') ?? 0;
                $reviewsCount = Review::where('property_id', $property->id)->count();

                $kpis = [
                    DashboardResponseBuilder::kpi('total_views', 'إجمالي المشاهدات', $totalViews, null, icon: 'eye'),
                    DashboardResponseBuilder::kpi('unique_viewers', 'مشاهدون فريدون', $uniqueViewers, null, icon: 'users'),
                    DashboardResponseBuilder::kpi('favorites', 'في المفضلات', $favoritesCount, null, icon: 'heart'),
                    DashboardResponseBuilder::kpi('leads_count', 'عملاء محتملون', $leadsFromProperty, null, icon: 'users'),
                    DashboardResponseBuilder::kpi('appointments', 'مواعيد', $appointments, null, icon: 'calendar'),
                    DashboardResponseBuilder::kpi('average_rating', 'متوسط التقييم', round((float) $averageRating, 2), null, format: 'rating', icon: 'star'),
                    DashboardResponseBuilder::kpi('reviews_count', 'عدد التقييمات', $reviewsCount, null, icon: 'message'),
                ];

                // 2) Views trend (line chart)
                $views = PropertyView::where('property_id', $property->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                    ->groupBy('date')
                    ->pluck('count', 'date');

                $points = [];
                for ($i = 0; $i < $days; $i++) {
                    $date = $from->addDays($i)->toDateString();
                    $points[] = [
                        'date' => $date,
                        'count' => (int) ($views[$date] ?? 0),
                    ];
                }

                // 3) Conversion funnel
                $funnel = [
                    ['stage' => 'view', 'label' => 'مشاهدة', 'count' => $totalViews],
                    ['stage' => 'favorite', 'label' => 'حفظ في المفضلات', 'count' => $favoritesCount],
                    ['stage' => 'contact', 'label' => 'تواصل/عميل محتمل', 'count' => $leadsFromProperty],
                    ['stage' => 'appointment', 'label' => 'موعد', 'count' => $appointments],
                ];

                // 4) Traffic sources (where users came from)
                $directViews = PropertyView::where('property_id', $property->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNull('user_id')
                    ->count();

                $registeredViews = PropertyView::where('property_id', $property->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNotNull('user_id')
                    ->count();

                $sponsoredViews = 0;
                if ($property->ads()->exists()) {
                    $adIds = $property->ads()->pluck('id');
                    $sponsoredViews = AdView::whereIn('ad_id', $adIds)
                        ->whereBetween('viewed_at', [$from, $to])
                        ->count();
                }

                $trafficSources = DashboardResponseBuilder::distribution(
                    dimension: 'traffic_source',
                    items: [
                        ['key' => 'direct', 'label' => 'مباشر', 'count' => $directViews],
                        ['key' => 'registered', 'label' => 'مستخدمون مسجّلون', 'count' => $registeredViews],
                        ['key' => 'sponsored', 'label' => 'إعلانات الرعاية', 'count' => $sponsoredViews],
                    ],
                    total: $directViews + $registeredViews + $sponsoredViews
                );

                // 5) Days on market
                $daysOnMarket = (int) $property->created_at->diffInDays(now());

                // 6) Price history (آخر 10 تغييرات)
                $priceHistory = $property->priceHistory()
                    ->orderByDesc('changed_at')
                    ->limit(10)
                    ->get(['old_price', 'new_price', 'changed_at'])
                    ->toArray();

                // 7) Reviews list
                $reviews = Review::where('property_id', $property->id)
                    ->with('reviewer:id,name')
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get()
                    ->map(fn($r) => [
                        'id' => $r->id,
                        'rating' => $r->rating,
                        'comment' => $r->comment,
                        'reviewer_name' => $r->reviewer?->name,
                        'created_at' => $r->created_at?->format('Y-m-d H:i:s'),
                    ])
                    ->toArray();

                // 8) Competitor properties (نفس المدينة ونفس الفئة ونطاق سعري ±20%)
                $competitors = Property::query()
                    ->where('id', '!=', $property->id)
                    ->where('city_id', $property->city_id)
                    ->where('property_type', $property->property_type)
                    ->whereBetween('price', [
                        (float) $property->price * 0.8,
                        (float) $property->price * 1.2,
                    ])
                    ->where('status', PropertyStatus::APPROVED)
                    ->withCount('views')
                    ->orderByDesc('views_count')
                    ->limit(5)
                    ->get(['id', 'name', 'price', 'currency', 'views'])
                    ->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => (float) $p->price,
                        'currency' => $p->currency,
                        'views' => (int) $p->views_count,
                    ])
                    ->toArray();

                return [
                    'property' => [
                        'id' => $property->id,
                        'name' => $property->name,
                        'status' => $property->status?->value,
                        'price' => (float) $property->price,
                        'currency' => $property->currency,
                        'publisher_name' => $property->publisher?->name,
                        'city_name' => $property->city?->name,
                        'country_name' => $property->country?->name,
                        'main_image' => $property->main_image_url,
                    ],
                    'filter' => $filter->toArray(),
                    'kpis' => $kpis,
                    'charts' => [
                        DashboardResponseBuilder::timeSeries('views', $points),
                    ],
                    'funnel' => $funnel,
                    'distributions' => [
                        'traffic_sources' => $trafficSources,
                    ],
                    'meta' => [
                        'days_on_market' => $daysOnMarket,
                    ],
                    'lists' => [
                        'price_history' => $priceHistory,
                        'reviews' => $reviews,
                        'competitors' => DashboardResponseBuilder::topList('العقارات المنافسة', $competitors, 'views'),
                    ],
                ];
            }
        );
    }

    public function clearCache(int $propertyId): void
    {
        $this->cache->flushScope('property');
    }
}
```

### ملاحظة: علاقة `priceHistory` في Property

إذا لم تكن `priceHistory` علاقة موجودة على `Property`، نتجاهلها ونُعيد `[]`. (التطبيق الكامل يحتاج جدول `property_price_history` يُضاف لاحقاً.)

> إذا لم تكن موجودة في النظام وقت التنفيذ، تُحذف من `show()` مع `[]` كقيمة افتراضية.

---

## المرحلة 2: `PropertyStatsController` (~ 30 دقيقة)

`Modules/Statistics/Http/Controllers/PropertyStatsController.php` (يحلّ محل الـ stub):

```php
<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Statistics\Services\PeriodResolver;
use Modules\Statistics\Services\PropertyStatsService;

class PropertyStatsController extends Controller
{
    use ApiResponses, ApplyPermissions, ValidatesRequests;

    public function __construct(
        protected readonly PropertyStatsService $stats,
        protected readonly PeriodResolver $periodResolver
    ) {
        $this->applyPermissions(
            'statistics',
            [],
            [
                'show' => 'view',
            ]
        );
    }

    public function show(Request $request, int $property): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);
        $data = $this->stats->show($property, $filter);
        return $this->successResponse($data);
    }
}
```

---

## المرحلة 3: `MarketStatsService` (~ 2.5 ساعة)

`Modules/Statistics/Services/MarketStatsService.php` (يحلّ محل الـ stub):

```php
<?php

namespace Modules\Statistics\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\Statistics\DTOs\StatsFilterDTO;
use Modules\Statistics\Enums\StatsPeriod;

class MarketStatsService extends BaseService
{
    public const CACHE_TTL_SECONDS = 600;

    public const CACHE_TAG = 'market_stats';

    public function __construct(
        protected readonly StatsCacheHelper $cache
    ) {}

    public function overview(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'market',
            'overview',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildOverview($filter)
        );
    }

    public function byCity(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'market',
            'by-city',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildByCity($filter)
        );
    }

    public function byCategory(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'market',
            'by-category',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildByCategory($filter)
        );
    }

    public function byPriceRange(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'market',
            'by-price-range',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildByPriceRange($filter)
        );
    }

    public function topViewed(StatsFilterDTO $filter, int $limit = 10): array
    {
        return $this->cache->remember(
            'market',
            'top-viewed',
            ['f' => $filter->toArray(), 'limit' => $limit],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildTopViewed($filter, $limit)
        );
    }

    public function topSaved(StatsFilterDTO $filter, int $limit = 10): array
    {
        return $this->cache->remember(
            'market',
            'top-saved',
            ['f' => $filter->toArray(), 'limit' => $limit],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildTopSaved($filter, $limit)
        );
    }

    public function listingsTrend(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'market',
            'listings-trend',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildListingsTrend($filter)
        );
    }

    // ============================================
    // BUILDERS
    // ============================================

    protected function baseQuery(StatsFilterDTO $filter)
    {
        return Property::query()
            ->where('status', PropertyStatus::APPROVED)
            ->whereBetween('created_at', [$filter->range->from, $filter->range->to]);
    }

    protected function buildOverview(StatsFilterDTO $filter): array
    {
        $base = $this->baseQuery($filter);

        $total = $base->count();
        $previousTotal = $filter->previousRange
            ? (clone $base)->whereBetween('created_at', [$filter->previousRange->from, $filter->previousRange->to])->count()
            : null;

        $avgPrice = $base->avg('price');
        $previousAvgPrice = $filter->previousRange
            ? (clone $base)->whereBetween('created_at', [$filter->previousRange->from, $filter->previousRange->to])->avg('price')
            : null;

        $sold = $base->where('status', PropertyStatus::SOLD)->count();
        $previousSold = $filter->previousRange
            ? (clone $base)->whereBetween('created_at', [$filter->previousRange->from, $filter->previousRange->to])->where('status', PropertyStatus::SOLD)->count()
            : null;

        $kpis = [
            DashboardResponseBuilder::kpi('total_properties', 'إجمالي العقارات', $total, $previousTotal, icon: 'building'),
            DashboardResponseBuilder::kpi('avg_price', 'متوسط السعر', round((float) $avgPrice, 2), $previousAvgPrice ? round((float) $previousAvgPrice, 2) : null, format: 'currency', icon: 'money'),
            DashboardResponseBuilder::kpi('sold_count', 'عقارات مباعة', $sold, $previousSold, icon: 'check'),
        ];

        return [
            'filter' => $filter->toArray(),
            'kpis' => $kpis,
        ];
    }

    protected function buildByCity(StatsFilterDTO $filter): array
    {
        $rows = $this->baseQuery($filter)
            ->select(
                'city_id',
                DB::raw('count(*) as count'),
                DB::raw('avg(price) as avg_price'),
                DB::raw('avg(area) as avg_area')
            )
            ->whereNotNull('city_id')
            ->groupBy('city_id')
            ->with('city:id,name')
            ->orderByDesc('count')
            ->get();

        $items = $rows->map(fn($r) => [
            'city_id' => $r->city_id,
            'city_name' => $r->city?->name ?? 'غير محدد',
            'count' => (int) $r->count,
            'avg_price' => round((float) $r->avg_price, 2),
            'avg_area' => round((float) $r->avg_area, 2),
        ])->toArray();

        return DashboardResponseBuilder::distribution('city', $items, array_sum(array_column($items, 'count')));
    }

    protected function buildByCategory(StatsFilterDTO $filter): array
    {
        $rows = $this->baseQuery($filter)
            ->select('property_type', DB::raw('count(*) as count'))
            ->groupBy('property_type')
            ->pluck('count', 'property_type');

        $items = [];
        $total = 0;
        foreach ($rows as $type => $count) {
            $items[] = [
                'key' => $type,
                'label' => $type,
                'count' => (int) $count,
            ];
            $total += $count;
        }

        return DashboardResponseBuilder::distribution('category', $items, $total);
    }

    protected function buildByPriceRange(StatsFilterDTO $filter): array
    {
        $ranges = [
            ['key' => '0-100k', 'label' => 'أقل من 100 ألف', 'min' => 0, 'max' => 100000],
            ['key' => '100k-300k', 'label' => '100 - 300 ألف', 'min' => 100000, 'max' => 300000],
            ['key' => '300k-500k', 'label' => '300 - 500 ألف', 'min' => 300000, 'max' => 500000],
            ['key' => '500k-1m', 'label' => '500 ألف - 1 مليون', 'min' => 500000, 'max' => 1000000],
            ['key' => '1m+', 'label' => 'أكثر من مليون', 'min' => 1000000, 'max' => null],
        ];

        $items = [];
        $total = 0;
        foreach ($ranges as $range) {
            $q = $this->baseQuery($filter);
            $q->where('price', '>=', $range['min']);
            if ($range['max'] !== null) {
                $q->where('price', '<', $range['max']);
            }
            $count = $q->count();
            $items[] = [
                'key' => $range['key'],
                'label' => $range['label'],
                'count' => $count,
            ];
            $total += $count;
        }

        return DashboardResponseBuilder::distribution('price_range', $items, $total);
    }

    protected function buildTopViewed(StatsFilterDTO $filter, int $limit): array
    {
        $properties = $this->baseQuery($filter)
            ->orderByDesc('views')
            ->limit($limit)
            ->get(['id', 'name', 'price', 'currency', 'views', 'city_id'])
            ->load('city:id,name');

        $items = $properties->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'city' => $p->city?->name,
            'price' => (float) $p->price,
            'currency' => $p->currency,
            'views' => (int) $p->views,
        ])->toArray();

        return DashboardResponseBuilder::topList('أكثر 10 عقارات مشاهدة', $items, 'views');
    }

    protected function buildTopSaved(StatsFilterDTO $filter, int $limit): array
    {
        $properties = $this->baseQuery($filter)
            ->withCount('favoritedBy as favorites_count')
            ->orderByDesc('favorites_count')
            ->limit($limit)
            ->get(['id', 'name', 'price', 'currency', 'city_id'])
            ->load('city:id,name');

        $items = $properties->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'city' => $p->city?->name,
            'price' => (float) $p->price,
            'currency' => $p->currency,
            'favorites_count' => (int) $p->favorites_count,
        ])->toArray();

        return DashboardResponseBuilder::topList('أكثر 10 عقارات حجوزاً في المفضلات', $items, 'favorites_count');
    }

    protected function buildListingsTrend(StatsFilterDTO $filter): array
    {
        $days = $filter->days();
        $from = $filter->range->from;

        $counts = $this->baseQuery($filter)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $points = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->addDays($i)->toDateString();
            $points[] = [
                'date' => $date,
                'count' => (int) ($counts[$date] ?? 0),
            ];
        }

        return DashboardResponseBuilder::timeSeries('listings', $points);
    }

    public function clearCache(): void
    {
        $this->cache->flushScope('market');
    }
}
```

---

## المرحلة 4: `MarketStatsController` (~ 30 دقيقة)

`Modules/Statistics/Http/Controllers/MarketStatsController.php` (يحلّ محل الـ stub):

```php
<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Statistics\Services\MarketStatsService;
use Modules\Statistics\Services\PeriodResolver;

class MarketStatsController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected readonly MarketStatsService $stats,
        protected readonly PeriodResolver $periodResolver
    ) {}

    public function overview(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->overview($this->periodResolver->fromRequest($request))
        );
    }

    public function byCity(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->byCity($this->periodResolver->fromRequest($request))
        );
    }

    public function byCategory(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->byCategory($this->periodResolver->fromRequest($request))
        );
    }

    public function byPriceRange(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->byPriceRange($this->periodResolver->fromRequest($request))
        );
    }

    public function topViewed(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        return $this->successResponse(
            $this->stats->topViewed($this->periodResolver->fromRequest($request), $limit)
        );
    }

    public function topSaved(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        return $this->successResponse(
            $this->stats->topSaved($this->periodResolver->fromRequest($request), $limit)
        );
    }

    public function listingsTrend(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->listingsTrend($this->periodResolver->fromRequest($request))
        );
    }
}
```

> **ملاحظة:** لا نضع `auth:sanctum` على market routes — هي public كما هو محدد في routes/api.php من الخطة #25.

---

## المرحلة 5: Cache invalidation للعقار والسوق (~ 30 دقيقة)

في `Modules/Statistics/Providers/CacheInvalidationServiceProvider.php` (أضف):

```php
use Modules\RealEstate\Events\PropertyApproved;
use Modules\RealEstate\Events\PropertyCreated;
use Modules\RealEstate\Events\PropertyDeleted;
use Modules\RealEstate\Events\PropertyUpdated;
use Modules\Statistics\Services\StatsCacheHelper;

// داخل boot():
Event::listen(PropertyCreated::class, fn() => $helper->flushScope('market'));
Event::listen(PropertyApproved::class, fn() => $helper->flushScope('market'));
Event::listen(PropertyUpdated::class, fn() => $helper->flushScope('market'));
Event::listen(PropertyDeleted::class, fn() => $helper->flushScope('market'));

// PropertyViewed يُمسح كاش العقار المحدد
Event::listen(PropertyViewed::class, function ($event) use ($helper) {
    if ($event->property?->id) {
        $helper->flushScope('property');
    }
});
```

> إذا لم تكن Events موجودة، استخدم `Property::saved()` Observer.

---

## المرحلة 6: اختبارات شاملة (~ 2.5 ساعة)

### 6.1 `PropertyStatsTest`

`Modules/Statistics/Tests/PropertyStatsTest.php`:

```php
<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Entities\Review;
use Modules\RealEstate\Enums\PropertyStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyStatsTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'statistics.view', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo('statistics.view');

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_show_returns_seven_kpis(): void
    {
        $property = Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $response->assertStatus(200);
        $this->assertCount(7, $response->json('data.kpis'));
    }

    public function test_show_counts_views_in_range(): void
    {
        $property = Property::factory()->create(['publisher_id' => $this->trader->id]);
        PropertyView::factory()->count(5)->create([
            'property_id' => $property->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(5, $kpis->firstWhere('key', 'total_views')['value']);
    }

    public function test_show_calculates_funnel(): void
    {
        $property = Property::factory()->create(['publisher_id' => $this->trader->id]);
        PropertyView::factory()->count(10)->create(['property_id' => $property->id]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $funnel = $response->json('data.funnel');
        $this->assertEquals('view', $funnel[0]['stage']);
        $this->assertEquals(10, $funnel[0]['count']);
    }

    public function test_show_calculates_days_on_market(): void
    {
        $property = Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'created_at' => now()->subDays(15),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/properties/{$property->id}/stats?period=last_30_days");

        $this->assertEquals(15, $response->json('data.meta.days_on_market'));
    }

    public function test_show_404_for_missing_property(): void
    {
        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/properties/99999/stats?period=last_30_days');

        $response->assertStatus(404);
    }

    public function test_show_requires_auth(): void
    {
        $property = Property::factory()->create(['publisher_id' => $this->trader->id]);

        $response = $this->getJson("/api/dashboard/properties/{$property->id}/stats");
        $response->assertStatus(401);
    }
}
```

### 6.2 `MarketStatsTest`

`Modules/Statistics/Tests/MarketStatsTest.php`:

```php
<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Tests\TestCase;

class MarketStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_overview_publicly_accessible(): void
    {
        $response = $this->getJson('/api/market/overview?period=last_30_days');
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data.kpis')));
    }

    public function test_overview_counts_approved_properties(): void
    {
        Property::factory()->count(3)->create(['status' => PropertyStatus::APPROVED]);
        Property::factory()->create(['status' => PropertyStatus::PENDING]);

        $response = $this->getJson('/api/market/overview?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(3, $kpis->firstWhere('key', 'total_properties')['value']);
    }

    public function test_by_city_distribution(): void
    {
        $city = City::first();
        Property::factory()->count(2)->create([
            'status' => PropertyStatus::APPROVED,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson('/api/market/by-city?period=last_30_days');
        $response->assertStatus(200);

        $items = $response->json('data.items');
        $cityItem = collect($items)->firstWhere('city_id', $city->id);
        $this->assertNotNull($cityItem);
        $this->assertEquals(2, $cityItem['count']);
    }

    public function test_by_price_range_distribution(): void
    {
        Property::factory()->create(['status' => PropertyStatus::APPROVED, 'price' => 50000]);
        Property::factory()->create(['status' => PropertyStatus::APPROVED, 'price' => 200000]);
        Property::factory()->create(['status' => PropertyStatus::APPROVED, 'price' => 2000000]);

        $response = $this->getJson('/api/market/by-price-range?period=last_30_days');
        $response->assertStatus(200);

        $items = collect($response->json('data.items'));
        $this->assertEquals(1, $items->firstWhere('key', '0-100k')['count']);
        $this->assertEquals(1, $items->firstWhere('key', '100k-300k')['count']);
        $this->assertEquals(1, $items->firstWhere('key', '1m+')['count']);
    }

    public function test_top_viewed_orders_by_views(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'views' => 100,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'views' => 500,
        ]);

        $response = $this->getJson('/api/market/top-viewed?period=last_30_days&limit=10');
        $response->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertEquals(500, $items[0]['views']);
    }

    public function test_listings_trend_returns_daily_points(): void
    {
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->getJson('/api/market/listings-trend?period=last_7_days');
        $response->assertStatus(200);
        $this->assertCount(7, $response->json('data.points'));
    }

    public function test_market_excludes_pending_properties(): void
    {
        Property::factory()->create(['status' => PropertyStatus::PENDING]);
        Property::factory()->create(['status' => PropertyStatus::REJECTED]);

        $response = $this->getJson('/api/market/overview?period=last_30_days');
        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(0, $kpis->firstWhere('key', 'total_properties')['value']);
    }
}
```

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (PropertyStatsService) | مستقل | #25 |
| 2 (PropertyStatsController) | يعتمد على 1 | #25 + 1 |
| 3 (MarketStatsService) | مستقل عن Property | #25 |
| 4 (MarketStatsController) | يعتمد على 3 | #25 + 3 |
| 5 (Listeners) | مستقل | #25 |
| 6 (Tests) | تعتمد على 1-4 | #25 + 1-4 |

> **ملاحظة:** المرحلتان 1+3 تعملان بالتوازي. المرحلتان 2+4 تعملان بالتوازي. كل هذه تعمل بالتوازي مع خطة #26 (لوحة التاجر) و #28 (لوحة الأدمن).

---

## معايير القبول

- [ ] `PropertyStatsService` يحوي `show()` و `clearCache()`.
- [ ] `PropertyStatsController` يحوي action `show` مع `applyPermissions`.
- [ ] `GET /api/dashboard/properties/{id}/stats?period=last_30_days` يُعيد 7 KPIs + funnel + distributions + price history + reviews + competitors.
- [ ] funnel يحوي 4 stages (view, favorite, contact, appointment).
- [ ] days_on_market محسوب صحيح.
- [ ] 404 للعقار غير الموجود.
- [ ] `MarketStatsService` يحوي 7 methods (overview, byCity, byCategory, byPriceRange, topViewed, topSaved, listingsTrend).
- [ ] `MarketStatsController` يحوي 7 actions (بدون applyPermissions — public).
- [ ] كل endpoints `/api/market/*` تعمل بدون auth.
- [ ] `byPriceRange` يحوي 5 شرائح: 0-100k, 100k-300k, 300k-500k, 500k-1m, 1m+.
- [ ] العقارات في الانتظار والمرفوضة مستبعدة من إحصائيات السوق.
- [ ] topViewed مرتّب تنازلياً بالمشاهدات.
- [ ] listingsTrend يُعيد نقطة لكل يوم في الفترة.
- [ ] Cache يعمل (5 دقائق property، 10 دقائق market) ويُمسح عند تغيّر العقار.
- [ ] `php artisan test --testsuite=Modules --filter="PropertyStatsTest|MarketStatsTest"` ينجح.
- [ ] `composer pint` يمر بدون أخطاء.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
