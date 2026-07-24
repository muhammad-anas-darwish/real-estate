# المهمة #26: لوحة إحصائيات التاجر (Trader Dashboard)

> **التقرير المصدر:** `docs/ideas/statistics-and-dashboards/report.md` (السطور 30–60 "لوحة التاجر"، السيناريو 1، القواعد 1–10، معايير القبول "اللوحة التاجر")
> **الهدف:** بناء لوحة إحصائيات كاملة للتاجر تكشف: 7 بطاقات KPI، 4 رسوم بيانية، 4 قوائم مختصرة، مقارنة تلقائية مع الفترة السابقة، وتصدير CSV لأداء العقارات.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 عالية
> **الجهد المقدّر:** 8–12 ساعة
> **الاعتمادية:** يجب أن يسبقها #25 (Statistics Foundation).
> **راجع:** `docs/ideas/statistics-and-dashboards/report.md` (السطور 30–60 لوحة التاجر، 160–175 السيناريو 1، 180–200 القواعد، 230–245 معايير القبول)

---

## الوضع الحالي

لا توجد لوحة إحصائيات للتاجر. التاجر لا يستطيع رؤية عدد مشاهدات عقاراته، أو عملائه المحتملين خلال فترة، أو مقارنة أدائه مع الفترة السابقة. الـ `LeadStatsService` في `Modules/Crm/Services/` يقدّم ملخّص CRM فقط (4 مفاتيح)، لكنه لا يدمج بيانات العقارات، العقود، الإعلانات، التقييمات.

هذه الخطة تنشئ:
- `Modules/Statistics/Services/TraderStatsService.php` — يستهلك `BaseService`، يُرجع payload موحّد.
- 10 endpoints تحت `/api/dashboard/trader/*` (مُعرَّفة في #25).
- 1 endpoint CSV export.
- اختبارات شاملة في `Modules/Statistics/Tests/TraderDashboardTest.php`.

> **ملاحظة:** الـ `TraderDashboardController` stub من الخطة #25 يُستبدل بـ controller كامل هنا. الـ `TraderStatsService` stub يُملأ.

---

## المرحلة 1: `TraderStatsService` — المنطق الأساسي (~ 4 ساعات)

`Modules/Statistics/Services/TraderStatsService.php` (يحلّ محل الـ stub):

```php
<?php

namespace Modules\Statistics\Services;

use App\Services\BaseService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdVisit;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Entities\Review;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\AdType;
use Modules\RealEstate\Enums\RentalCardStatus;
use Modules\RealEstate\Enums\ViewingStatus;
use Modules\Statistics\DTOs\StatsFilterDTO;

class TraderStatsService extends BaseService
{
    public const CACHE_TTL_SECONDS = 300;

    public const CACHE_TAG = 'trader_stats';

    public function __construct(
        protected readonly StatsCacheHelper $cache
    ) {}

    public function summary(int $traderId, StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'trader',
            'summary',
            ['trader' => $traderId, 'f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            function () use ($traderId, $filter) {
                $current = $this->baseMetrics($traderId, $filter->range);
                $previous = $filter->previousRange
                    ? $this->baseMetrics($traderId, $filter->previousRange)
                    : null;

                return DashboardResponseBuilder::build(
                    filter: $filter,
                    kpis: [
                        DashboardResponseBuilder::kpi(
                            'active_properties',
                            'العقارات الفعّالة',
                            $current['active_properties'],
                            $previous['active_properties'] ?? null,
                            icon: 'building'
                        ),
                        DashboardResponseBuilder::kpi(
                            'total_views',
                            'إجمالي المشاهدات',
                            $current['total_views'],
                            $previous['total_views'] ?? null,
                            icon: 'eye'
                        ),
                        DashboardResponseBuilder::kpi(
                            'new_leads',
                            'عملاء جدد',
                            $current['new_leads'],
                            $previous['new_leads'] ?? null,
                            icon: 'users'
                        ),
                        DashboardResponseBuilder::kpi(
                            'conversion_rate',
                            'معدل التحويل',
                            $current['conversion_rate'],
                            $previous['conversion_rate'] ?? null,
                            format: 'percent',
                            icon: 'target'
                        ),
                        DashboardResponseBuilder::kpi(
                            'average_rating',
                            'متوسط التقييم',
                            $current['average_rating'],
                            $previous['average_rating'] ?? null,
                            format: 'rating',
                            icon: 'star'
                        ),
                        DashboardResponseBuilder::kpi(
                            'active_rentals',
                            'عقود إيجار نشطة',
                            $current['active_rentals'],
                            $previous['active_rentals'] ?? null,
                            icon: 'file-contract'
                        ),
                        DashboardResponseBuilder::kpi(
                            'upcoming_appointments',
                            'مواعيد قادمة (هذا الأسبوع)',
                            $current['upcoming_appointments'],
                            null,
                            icon: 'calendar'
                        ),
                    ],
                );
            }
        );
    }

    public function viewsTrend(int $traderId, StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'trader',
            'views-trend',
            ['trader' => $traderId, 'f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            function () use ($traderId, $filter) {
                $days = $filter->days();
                $start = $filter->range->from;
                $end = $filter->range->to;

                $views = PropertyView::query()
                    ->whereIn('property_id', function ($q) use ($traderId) {
                        $q->select('id')->from('properties')->where('publisher_id', $traderId);
                    })
                    ->whereBetween('created_at', [$start, $end])
                    ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                    ->groupBy('date')
                    ->pluck('count', 'date');

                $points = [];
                for ($i = 0; $i < $days; $i++) {
                    $date = $start->addDays($i)->toDateString();
                    $points[] = [
                        'date' => $date,
                        'count' => (int) ($views[$date] ?? 0),
                    ];
                }

                return DashboardResponseBuilder::timeSeries('property_views', $points);
            }
        );
    }

    public function leadsByStatus(int $traderId, StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'trader',
            'leads-by-status',
            ['trader' => $traderId, 'f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            function () use ($traderId, $filter) {
                $counts = Lead::query()
                    ->where('trader_id', $traderId)
                    ->whereBetween('created_at', [$filter->range->from, $filter->range->to])
                    ->groupBy('status')
                    ->select('status', DB::raw('count(*) as count'))
                    ->pluck('count', 'status');

                $items = [];
                $total = 0;
                foreach (LeadStatus::cases() as $status) {
                    $count = (int) ($counts[$status->value] ?? 0);
                    $items[] = [
                        'key' => $status->value,
                        'label' => $status->label(),
                        'count' => $count,
                        'percentage' => 0,
                    ];
                    $total += $count;
                }

                foreach ($items as &$item) {
                    $item['percentage'] = $total > 0
                        ? round(($item['count'] / $total) * 100, 1)
                        : 0;
                }

                return DashboardResponseBuilder::distribution(
                    dimension: 'lead_status',
                    items: $items,
                    total: $total
                );
            }
        );
    }

    public function propertiesByStatus(int $traderId, StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'trader',
            'properties-by-status',
            ['trader' => $traderId, 'f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            function () use ($traderId) {
                $counts = Property::query()
                    ->where('publisher_id', $traderId)
                    ->groupBy('status')
                    ->select('status', DB::raw('count(*) as count'))
                    ->pluck('count', 'status');

                $items = [];
                $total = 0;
                foreach (['pending', 'approved', 'rejected', 'sold'] as $status) {
                    $count = (int) ($counts[$status] ?? 0);
                    $items[] = [
                        'key' => $status,
                        'label' => match ($status) {
                            'pending' => 'في الانتظار',
                            'approved' => 'فعّالة',
                            'rejected' => 'مرفوضة',
                            'sold' => 'مباعة',
                        },
                        'count' => $count,
                    ];
                    $total += $count;
                }

                return DashboardResponseBuilder::distribution(
                    dimension: 'property_status',
                    items: $items,
                    total: $total
                );
            }
        );
    }

    public function topProperties(int $traderId, StatsFilterDTO $filter, int $limit = 10): array
    {
        return $this->cache->remember(
            'trader',
            'top-properties',
            ['trader' => $traderId, 'f' => $filter->toArray(), 'limit' => $limit],
            self::CACHE_TTL_SECONDS,
            function () use ($traderId, $filter, $limit) {
                $properties = Property::query()
                    ->where('publisher_id', $traderId)
                    ->withCount([
                        'views as views_in_range' => function ($q) use ($filter) {
                            $q->whereBetween('created_at', [$filter->range->from, $filter->range->to]);
                        },
                        'favoritedBy as favorites_count',
                    ])
                    ->orderByDesc('views_in_range')
                    ->limit($limit)
                    ->get();

                $items = $properties->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'status' => $p->status?->value,
                        'views_in_range' => (int) $p->views_in_range,
                        'favorites_count' => (int) $p->favorites_count,
                        'main_image' => $p->main_image_url,
                    ];
                })->toArray();

                return DashboardResponseBuilder::topList(
                    title: 'أفضل العقارات أداءً',
                    items: $items,
                    sortBy: 'views_in_range'
                );
            }
        );
    }

    public function recentLeads(int $traderId, int $limit = 10): array
    {
        return $this->cache->remember(
            'trader',
            'recent-leads',
            ['trader' => $traderId, 'limit' => $limit],
            self::CACHE_TTL_SECONDS,
            function () use ($traderId, $limit) {
                $leads = Lead::query()
                    ->where('trader_id', $traderId)
                    ->active()
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get(['id', 'name', 'status', 'source', 'created_at']);

                return [
                    'title' => 'أحدث العملاء المحتملين',
                    'items' => $leads->map(fn($l) => [
                        'id' => $l->id,
                        'name' => $l->name,
                        'status' => $l->status?->value,
                        'status_label' => $l->status?->label(),
                        'source' => $l->source?->value,
                        'created_at' => $l->created_at?->format('Y-m-d H:i:s'),
                    ])->toArray(),
                ];
            }
        );
    }

    public function upcomingAppointments(int $traderId, int $days = 7): array
    {
        return $this->cache->remember(
            'trader',
            'upcoming-appointments',
            ['trader' => $traderId, 'days' => $days],
            self::CACHE_TTL_SECONDS,
            function () use ($traderId, $days) {
                $appointments = Appointment::query()
                    ->where('agent_id', $traderId)
                    ->where('scheduled_at', '>=', now())
                    ->where('scheduled_at', '<=', now()->addDays($days))
                    ->whereIn('status', [ViewingStatus::PENDING, ViewingStatus::CONFIRMED])
                    ->with(['property:id,name', 'user:id,name'])
                    ->orderBy('scheduled_at')
                    ->limit(5)
                    ->get();

                return [
                    'title' => 'المواعيد القادمة',
                    'items' => $appointments->map(fn($a) => [
                        'id' => $a->id,
                        'scheduled_at' => $a->scheduled_at?->format('Y-m-d H:i'),
                        'property_name' => $a->property?->name,
                        'contact_name' => $a->contact_name ?? $a->user?->name,
                        'status' => $a->status?->value,
                    ])->toArray(),
                ];
            }
        );
    }

    public function expiringRentals(int $traderId, int $withinDays = 30): array
    {
        return $this->cache->remember(
            'trader',
            'expiring-rentals',
            ['trader' => $traderId, 'within' => $withinDays],
            self::CACHE_TTL_SECONDS,
            function () use ($traderId, $withinDays) {
                $rentals = RentalCard::query()
                    ->where('owner_id', $traderId)
                    ->where('status', RentalCardStatus::ACTIVE)
                    ->whereBetween('end_date', [today(), today()->addDays($withinDays)])
                    ->with(['property:id,name', 'tenantUser:id,name'])
                    ->orderBy('end_date')
                    ->get();

                return [
                    'title' => "عقود إيجار تنتهي خلال {$withinDays} يوم",
                    'items' => $rentals->map(fn($r) => [
                        'id' => $r->id,
                        'property_name' => $r->property?->name,
                        'tenant_name' => $r->tenant_display_name,
                        'end_date' => $r->end_date?->toDateString(),
                        'days_remaining' => $r->days_remaining,
                    ])->toArray(),
                ];
            }
        );
    }

    public function sponsoredAdsSummary(int $traderId, StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'trader',
            'sponsored-ads',
            ['trader' => $traderId, 'f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            function () use ($traderId, $filter) {
                $ads = Ad::query()
                    ->where('type', AdType::SPONSORED)
                    ->where('user_id', $traderId)
                    ->whereIn('status', [AdStatus::ACTIVE, AdStatus::PAUSED])
                    ->get();

                $adIds = $ads->pluck('id');

                $views = AdView::whereIn('ad_id', $adIds)
                    ->whereBetween('viewed_at', [$filter->range->from, $filter->range->to])
                    ->count();

                $visits = AdVisit::whereIn('ad_id', $adIds)
                    ->whereBetween('visited_at', [$filter->range->from, $filter->range->to])
                    ->count();

                $totalSpent = $ads->sum('amount_paid');
                $activeCount = $ads->where('status', AdStatus::ACTIVE)->count();

                $items = $ads->map(function ($ad) use ($filter) {
                    $adViews = AdView::where('ad_id', $ad->id)
                        ->whereBetween('viewed_at', [$filter->range->from, $filter->range->to])
                        ->count();
                    $adVisits = AdVisit::where('ad_id', $ad->id)
                        ->whereBetween('visited_at', [$filter->range->from, $filter->range->to])
                        ->count();

                    return [
                        'id' => $ad->id,
                        'title' => $ad->title,
                        'status' => $ad->status?->value,
                        'amount_paid' => (float) $ad->amount_paid,
                        'views' => $adViews,
                        'visits' => $adVisits,
                        'cpr' => $adVisits > 0 ? round((float) $ad->amount_paid / $adVisits, 2) : 0,
                    ];
                })->toArray();

                return [
                    'kpis' => [
                        DashboardResponseBuilder::kpi('active_ads', 'إعلانات نشطة', $activeCount, null, icon: 'ad'),
                        DashboardResponseBuilder::kpi('total_spent', 'إجمالي الإنفاق', (float) $totalSpent, null, format: 'currency', icon: 'money'),
                        DashboardResponseBuilder::kpi('total_ad_views', 'مشاهدات الإعلانات', $views, null, icon: 'eye'),
                        DashboardResponseBuilder::kpi('total_ad_visits', 'زيارات الإعلانات', $visits, null, icon: 'click'),
                    ],
                    'ads' => $items,
                ];
            }
        );
    }

    public function exportProperties(int $traderId, StatsFilterDTO $filter): array
    {
        return Property::query()
            ->where('publisher_id', $traderId)
            ->withCount(['favoritedBy as favorites_count'])
            ->withCount([
                'views as views_in_range' => function ($q) use ($filter) {
                    $q->whereBetween('created_at', [$filter->range->from, $filter->range->to]);
                },
            ])
            ->get()
            ->map(function ($p) {
                return [
                    $p->id,
                    $p->name,
                    $p->status?->value,
                    $p->price,
                    $p->currency,
                    $p->views,
                    $p->views_in_range,
                    $p->favorites_count,
                    $p->created_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    public function clearCache(int $traderId): void
    {
        // Invalidate by tag flush (simpler, more reliable)
        $this->cache->flushScope('trader');
    }

    protected function baseMetrics(int $traderId, \Modules\Statistics\ValueObjects\DateRange $range): array
    {
        $from = $range->from;
        $to = $range->to;

        $activeProperties = Property::where('publisher_id', $traderId)
            ->where('status', 'approved')
            ->count();

        $propertyIds = Property::where('publisher_id', $traderId)->pluck('id');

        $totalViews = PropertyView::whereIn('property_id', $propertyIds)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $newLeads = Lead::where('trader_id', $traderId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $conversionRate = $activeProperties > 0
            ? round(($newLeads / $activeProperties) * 100, 2)
            : 0;

        $averageRating = Review::whereIn('reviewed_id', [$traderId])
            ->whereBetween('created_at', [$from, $to])
            ->avg('rating') ?? 0;

        $activeRentals = RentalCard::where('owner_id', $traderId)
            ->where('status', RentalCardStatus::ACTIVE)
            ->count();

        $upcomingAppointments = Appointment::where('agent_id', $traderId)
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->whereIn('status', [ViewingStatus::PENDING, ViewingStatus::CONFIRMED])
            ->count();

        return [
            'active_properties' => $activeProperties,
            'total_views' => $totalViews,
            'new_leads' => $newLeads,
            'conversion_rate' => $conversionRate,
            'average_rating' => round((float) $averageRating, 2),
            'active_rentals' => $activeRentals,
            'upcoming_appointments' => $upcomingAppointments,
        ];
    }
}
```

> **ملاحظة:** الـ `viewsCount` relation (from the `views` cast on the model) is the cumulative counter, not the per-period one. We use `withCount` with whereBetween for period-scoped counts.

---

## المرحلة 2: `TraderDashboardController` الكامل (~ 1.5 ساعة)

`Modules/Statistics/Http/Controllers/TraderDashboardController.php` (يحلّ محل الـ stub):

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
use Modules\Statistics\Services\TraderStatsService;

class TraderDashboardController extends Controller
{
    use ApiResponses, ApplyPermissions, ValidatesRequests;

    public function __construct(
        protected readonly TraderStatsService $stats,
        protected readonly PeriodResolver $periodResolver
    ) {
        $this->applyPermissions(
            'statistics',
            [],
            [
                'summary' => 'view',
                'viewsTrend' => 'view',
                'leadsByStatus' => 'view',
                'propertiesByStatus' => 'view',
                'topProperties' => 'view',
                'recentLeads' => 'view',
                'upcomingAppointments' => 'view',
                'expiringRentals' => 'view',
                'sponsoredAdsSummary' => 'view',
                'exportProperties' => 'export',
            ]
        );
    }

    public function summary(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);
        $data = $this->stats->summary(auth()->id(), $filter);
        return $this->successResponse($data);
    }

    public function viewsTrend(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);
        return $this->successResponse(
            $this->stats->viewsTrend(auth()->id(), $filter)
        );
    }

    public function leadsByStatus(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);
        return $this->successResponse(
            $this->stats->leadsByStatus(auth()->id(), $filter)
        );
    }

    public function propertiesByStatus(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->propertiesByStatus(auth()->id(), $this->periodResolver->fromRequest($request))
        );
    }

    public function topProperties(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);
        $limit = (int) $request->input('limit', 10);
        return $this->successResponse(
            $this->stats->topProperties(auth()->id(), $filter, $limit)
        );
    }

    public function recentLeads(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        return $this->successResponse(
            $this->stats->recentLeads(auth()->id(), $limit)
        );
    }

    public function upcomingAppointments(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 7);
        return $this->successResponse(
            $this->stats->upcomingAppointments(auth()->id(), $days)
        );
    }

    public function expiringRentals(Request $request): JsonResponse
    {
        $within = (int) $request->input('within_days', 30);
        return $this->successResponse(
            $this->stats->expiringRentals(auth()->id(), $within)
        );
    }

    public function sponsoredAdsSummary(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);
        return $this->successResponse(
            $this->stats->sponsoredAdsSummary(auth()->id(), $filter)
        );
    }

    public function exportProperties(Request $request)
    {
        $filter = $this->periodResolver->fromRequest($request);
        $rows = $this->stats->exportProperties(auth()->id(), $filter);

        $filename = 'trader-properties-'.now()->format('Ymd-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID', 'Name', 'Status', 'Price', 'Currency',
                'Total Views', 'Views In Range', 'Favorites', 'Created At',
            ]);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
```

---

## المرحلة 3: Cache invalidation listeners (~ 1 ساعة)

نحتاج لمسح كاش التاجر عند أي تغيّر في بياناته (إضافة عقار، عميل، موعد، عقد، تقييم، إعلان).

`Modules/Statistics/Providers/CacheInvalidationServiceProvider.php` (يُضاف أو يُدمج في `StatisticsServiceProvider`):

```php
<?php

namespace Modules\Statistics\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Crm\Events\LeadCreated;
use Modules\Crm\Events\LeadUpdated;
use Modules\RealEstate\Events\PropertyApproved;
use Modules\RealEstate\Events\PropertyRejected;
use Modules\RealEstate\Events\PropertyViewed;
use Modules\RealEstate\Events\ReviewCreated;
use Modules\Statistics\Services\StatsCacheHelper;

class CacheInvalidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $helper = app(StatsCacheHelper::class);

        // مسح الكاش عند أي تغيّر في بيانات التاجر
        // تُربط من خلال الـ Events الموجودة في كل module
        Event::listen(LeadCreated::class, fn() => $helper->flushScope('trader'));
        Event::listen(LeadUpdated::class, fn() => $helper->flushScope('trader'));
        Event::listen(PropertyApproved::class, fn() => $helper->flushScope('trader'));
        Event::listen(PropertyRejected::class, fn() => $helper->flushScope('trader'));
        Event::listen(ReviewCreated::class, fn() => $helper->flushScope('trader'));

        // PropertyViewed يُمسح كاش التاجر
        Event::listen(PropertyViewed::class, function ($event) use ($helper) {
            if ($event->property?->publisher_id) {
                $helper->flushScope('trader');
            }
        });
    }
}
```

> **ملاحظة:** إذا لم تكن هذه Events موجودة (مثل `LeadCreated`)، فالحل الأبسط هو تسجيل Observer على الـ Models مباشرة في `boot()`:
>
> ```php
> Property::saved(fn() => $helper->flushScope('trader'));
> Lead::saved(fn() => $helper->flushScope('trader'));
> // ...
> ```
> يُختار الأسلوب الأنسب حسب الـ Events/Listeners الموجودة في النظام وقت التنفيذ.

---

## المرحلة 4: اختبارات شاملة (~ 3 ساعات)

`Modules/Statistics/Tests/TraderDashboardTest.php`:

```php
<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Entities\Review;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\AdType;
use Modules\RealEstate\Enums\RentalCardStatus;
use Modules\RealEstate\Enums\ViewingStatus;
use Modules\Statistics\Enums\StatsPeriod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TraderDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected User $otherTrader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $this->otherTrader = User::factory()->create();
        $this->otherTrader->assignRole('trader');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    protected function seedPermissions(): void
    {
        $perms = ['statistics.view', 'statistics.export'];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo($perms);
    }

    protected function makeProperty(User $publisher, array $attrs = []): Property
    {
        return Property::factory()->create(array_merge([
            'publisher_id' => $publisher->id,
            'status' => 'approved',
        ], $attrs));
    }

    // ============================================
    // SUMMARY ENDPOINT
    // ============================================

    public function test_summary_returns_seven_kpis(): void
    {
        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'kpis' => [
                    '*' => ['key', 'label', 'value', 'format', 'icon'],
                ],
            ],
        ]);
        $this->assertCount(7, $response->json('data.kpis'));
    }

    public function test_summary_counts_active_properties(): void
    {
        $this->makeProperty($this->trader, ['status' => 'approved']);
        $this->makeProperty($this->trader, ['status' => 'approved']);
        $this->makeProperty($this->trader, ['status' => 'pending']);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $response->assertJsonPath('data.kpis.0.value', 2);
    }

    public function test_summary_counts_views_in_range(): void
    {
        $property = $this->makeProperty($this->trader);
        PropertyView::factory()->count(5)->create([
            'property_id' => $property->id,
            'created_at' => now()->subDays(2),
        ]);
        PropertyView::factory()->count(3)->create([
            'property_id' => $property->id,
            'created_at' => now()->subDays(40),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $viewsKpi = $kpis->firstWhere('key', 'total_views');
        $this->assertEquals(5, $viewsKpi['value']);
    }

    public function test_summary_counts_new_leads(): void
    {
        Lead::factory()->count(4)->create([
            'trader_id' => $this->trader->id,
            'created_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(4, $kpis->firstWhere('key', 'new_leads')['value']);
    }

    public function test_summary_isolated_between_traders(): void
    {
        Lead::factory()->count(3)->create(['trader_id' => $this->otherTrader->id]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(0, $kpis->firstWhere('key', 'new_leads')['value']);
    }

    public function test_summary_includes_comparison_with_previous_period(): void
    {
        // Current period: 5 leads
        Lead::factory()->count(5)->create([
            'trader_id' => $this->trader->id,
            'created_at' => now()->subDays(5),
        ]);
        // Previous period: 2 leads
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'created_at' => now()->subDays(40),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $newLeadsKpi = $kpis->firstWhere('key', 'new_leads');
        $this->assertEquals(5, $newLeadsKpi['value']);
        $this->assertEquals(2, $newLeadsKpi['previous_value']);
        $this->assertEquals('up', $newLeadsKpi['change_direction']);
        $this->assertGreaterThan(0, $newLeadsKpi['change_percent']);
    }

    public function test_summary_average_rating_calculated(): void
    {
        Review::factory()->create([
            'reviewed_id' => $this->trader->id,
            'rating' => 5,
        ]);
        Review::factory()->create([
            'reviewed_id' => $this->trader->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(4, $kpis->firstWhere('key', 'average_rating')['value']);
    }

    public function test_summary_active_rentals_calculated(): void
    {
        $property = $this->makeProperty($this->trader);
        RentalCard::factory()->count(2)->create([
            'owner_id' => $this->trader->id,
            'property_id' => $property->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);
        RentalCard::factory()->create([
            'owner_id' => $this->trader->id,
            'property_id' => $property->id,
            'status' => RentalCardStatus::ENDED,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(2, $kpis->firstWhere('key', 'active_rentals')['value']);
    }

    // ============================================
    // VIEWS TREND
    // ============================================

    public function test_views_trend_returns_daily_points(): void
    {
        $property = $this->makeProperty($this->trader);
        PropertyView::factory()->create([
            'property_id' => $property->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/views-trend?period=last_7_days');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['metric', 'unit', 'points' => ['*' => ['date', 'count']]],
        ]);
        $this->assertCount(7, $response->json('data.points'));
    }

    // ============================================
    // LEADS BY STATUS
    // ============================================

    public function test_leads_by_status_distribution(): void
    {
        Lead::factory()->count(3)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::NEW,
        ]);
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::CONTACTED,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/leads-by-status?period=last_30_days');

        $response->assertStatus(200);
        $items = collect($response->json('data.items'));
        $this->assertEquals(3, $items->firstWhere('key', 'new')['count']);
        $this->assertEquals(2, $items->firstWhere('key', 'contacted')['count']);
        $this->assertEquals(5, $response->json('data.total'));
    }

    // ============================================
    // PROPERTIES BY STATUS
    // ============================================

    public function test_properties_by_status_distribution(): void
    {
        $this->makeProperty($this->trader, ['status' => 'approved']);
        $this->makeProperty($this->trader, ['status' => 'pending']);
        $this->makeProperty($this->trader, ['status' => 'approved']);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/properties-by-status?period=last_30_days');

        $response->assertStatus(200);
        $items = collect($response->json('data.items'));
        $this->assertEquals(2, $items->firstWhere('key', 'approved')['count']);
        $this->assertEquals(1, $items->firstWhere('key', 'pending')['count']);
    }

    // ============================================
    // TOP PROPERTIES
    // ============================================

    public function test_top_properties_ordered_by_views(): void
    {
        $p1 = $this->makeProperty($this->trader);
        $p2 = $this->makeProperty($this->trader);
        $p3 = $this->makeProperty($this->trader);

        PropertyView::factory()->count(10)->create(['property_id' => $p2->id, 'created_at' => now()->subDays(2)]);
        PropertyView::factory()->count(5)->create(['property_id' => $p1->id, 'created_at' => now()->subDays(2)]);
        PropertyView::factory()->count(1)->create(['property_id' => $p3->id, 'created_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/top-properties?period=last_30_days&limit=10');

        $response->assertStatus(200);
        $items = $response->json('data.items');
        $this->assertEquals($p2->id, $items[0]['id']);
        $this->assertEquals(10, $items[0]['views_in_range']);
    }

    // ============================================
    // RECENT LEADS
    // ============================================

    public function test_recent_leads_returns_latest(): void
    {
        Lead::factory()->count(3)->create(['trader_id' => $this->trader->id]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/recent-leads?limit=5');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.items'));
    }

    // ============================================
    // UPCOMING APPOINTMENTS
    // ============================================

    public function test_upcoming_appointments_only_future(): void
    {
        Appointment::factory()->create([
            'agent_id' => $this->trader->id,
            'type' => 'viewing',
            'scheduled_at' => now()->addDays(2),
            'status' => ViewingStatus::PENDING,
        ]);
        Appointment::factory()->create([
            'agent_id' => $this->trader->id,
            'type' => 'viewing',
            'scheduled_at' => now()->subDays(1),
            'status' => ViewingStatus::PENDING,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/upcoming-appointments?days=7');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.items'));
    }

    // ============================================
    // EXPIRING RENTALS
    // ============================================

    public function test_expiring_rentals_within_30_days(): void
    {
        $property = $this->makeProperty($this->trader);
        RentalCard::factory()->create([
            'owner_id' => $this->trader->id,
            'property_id' => $property->id,
            'end_date' => now()->addDays(15),
            'status' => RentalCardStatus::ACTIVE,
        ]);
        RentalCard::factory()->create([
            'owner_id' => $this->trader->id,
            'property_id' => $property->id,
            'end_date' => now()->addDays(60),
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/expiring-rentals?within_days=30');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.items'));
    }

    // ============================================
    // SPONSORED ADS
    // ============================================

    public function test_sponsored_ads_summary_calculates_spend(): void
    {
        Ad::factory()->create([
            'type' => AdType::SPONSORED,
            'user_id' => $this->trader->id,
            'status' => AdStatus::ACTIVE,
            'amount_paid' => 100,
        ]);
        Ad::factory()->create([
            'type' => AdType::SPONSORED,
            'user_id' => $this->trader->id,
            'status' => AdStatus::PAUSED,
            'amount_paid' => 50,
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/sponsored-ads-summary?period=last_30_days');

        $response->assertStatus(200);
        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(2, $kpis->firstWhere('key', 'active_ads')['value']);
        $this->assertEquals(150, $kpis->firstWhere('key', 'total_spent')['value']);
    }

    // ============================================
    // CSV EXPORT
    // ============================================

    public function test_csv_export_returns_valid_csv(): void
    {
        $this->makeProperty($this->trader);
        $this->makeProperty($this->trader);

        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/trader/export/properties?period=last_30_days');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        $this->assertCount(3, $lines); // header + 2 properties
        $this->assertStringContainsString('Name', $lines[0]);
    }

    public function test_csv_export_isolated_between_traders(): void
    {
        $this->makeProperty($this->trader);
        $this->makeProperty($this->otherTrader);

        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/trader/export/properties?period=last_30_days');

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        $this->assertCount(2, $lines); // header + 1 property (trader's only)
    }

    // ============================================
    // AUTHORIZATION
    // ============================================

    public function test_non_trader_user_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/dashboard/trader/summary');

        $response->assertStatus(403);
    }

    public function test_guest_user_gets_401(): void
    {
        $response = $this->getJson('/api/dashboard/trader/summary');
        $response->assertStatus(401);
    }

    // ============================================
    // CACHE
    // ============================================

    public function test_cache_works_for_repeated_calls(): void
    {
        $this->makeProperty($this->trader);

        $first = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');
        $first->assertStatus(200);

        // Second call returns the same value
        $second = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');
        $second->assertStatus(200);

        $this->assertEquals(
            $first->json('data.kpis.0.value'),
            $second->json('data.kpis.0.value')
        );
    }
}
```

> **ملاحظة:** الـ `Property::factory()`, `Lead::factory()`, `Appointment::factory()`, `RentalCard::factory()`, `Review::factory()`, `Ad::factory()` يجب أن تكون موجودة. إذا لم تكن، تُضاف minimal factory states في المرحلة 0 (التحضير).

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Service) | مستقل | #25 |
| 2 (Controller) | يستخدم Service من المرحلة 1 | #25 + 1 |
| 3 (Listeners) | مستقل عن Controller | #25 |
| 4 (Tests) | تعتمد على 1 + 2 | #25 + 1 + 2 |

> **ملاحظة:** الخطة #27 (Property & Market) تعمل بالتوازي الكامل مع #26. الخطة #28 (Admin) تعمل بالتوازي أيضاً.

---

## معايير القبول

- [ ] `TraderStatsService` يحوي 9 methods (summary, viewsTrend, leadsByStatus, propertiesByStatus, topProperties, recentLeads, upcomingAppointments, expiringRentals, sponsoredAdsSummary, exportProperties).
- [ ] `TraderDashboardController` يحوي 10 actions + applyPermissions لكل method.
- [ ] `GET /api/dashboard/trader/summary?period=last_30_days` يُعيد 7 KPIs مع previous_value و change_percent.
- [ ] `GET /api/dashboard/trader/views-trend` يُعيد time series بـ 30 نقطة (لفترة 30 يوم).
- [ ] `GET /api/dashboard/trader/leads-by-status` يُعيد distribution لكل حالات LeadStatus.
- [ ] `GET /api/dashboard/trader/properties-by-status` يُعيد distribution لـ 4 حالات.
- [ ] `GET /api/dashboard/trader/top-properties?limit=10` يُعيد 10 عقارات مرتّبة بالمشاهدات.
- [ ] `GET /api/dashboard/trader/recent-leads?limit=10` يُعيد آخر العملاء.
- [ ] `GET /api/dashboard/trader/upcoming-appointments?days=7` يُعيد المواعيد القادمة.
- [ ] `GET /api/dashboard/trader/expiring-rentals?within_days=30` يُعيد العقود المنتهية قريباً.
- [ ] `GET /api/dashboard/trader/sponsored-ads-summary?period=last_30_days` يُعيد KPIs و قائمة الإعلانات.
- [ ] `GET /api/dashboard/trader/export/properties` يُنزّل CSV مع 8 أعمدة.
- [ ] مستخدم بغير دور `trader` يحصل على 403.
- [ ] تاجر آخر لا يظهر في النتائج (الاختبارات تختبر العزل).
- [ ] Cache يعمل (5 دقائق TTL) ويُمسح عند تغيّر البيانات (listener).
- [ ] كل endpoint يقبل `?period=` و `?from=` `?to=` (للفترات المخصصة).
- [ ] `php artisan test --testsuite=Modules --filter=TraderDashboardTest` ينجح (جميع الاختبارات).
- [ ] `composer pint` يمر بدون أخطاء.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
