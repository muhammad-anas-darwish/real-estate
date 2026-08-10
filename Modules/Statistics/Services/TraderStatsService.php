<?php

namespace Modules\Statistics\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\AdVisit;
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

                $kpis = [
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
                ];

                return [
                    'filter' => $filter->toArray(),
                    'generated_at' => now()->toIso8601String(),
                    'kpis' => $kpis,
                ];
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

                return [
                    'metric' => 'property_views',
                    'unit' => 'count',
                    'points' => $points,
                ];
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

                return [
                    'dimension' => 'lead_status',
                    'items' => $items,
                    'total' => $total,
                ];
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

                return [
                    'dimension' => 'property_status',
                    'items' => $items,
                    'total' => $total,
                ];
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

                return [
                    'title' => 'أفضل العقارات أداءً',
                    'items' => $items,
                    'sort_by' => 'views_in_range',
                ];
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
                    'items' => $leads->map(fn ($l) => [
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
                    'items' => $appointments->map(fn ($a) => [
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
                    'items' => $rentals->map(fn ($r) => [
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

                $totalSpent = (float) $ads->sum('amount_paid');
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
                        DashboardResponseBuilder::kpi('total_spent', 'إجمالي الإنفاق', $totalSpent, null, format: 'currency', icon: 'money'),
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

    public function clearCache(): void
    {
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

        $averageRating = Review::where('reviewed_id', $traderId)
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
