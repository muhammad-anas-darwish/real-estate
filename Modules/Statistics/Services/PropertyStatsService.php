<?php

namespace Modules\Statistics\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Entities\Lead;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Entities\Review;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\Statistics\DTOs\StatsFilterDTO;

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

                // 2) Views trend
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

                // 4) Traffic sources
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

                $trafficSources = [
                    'dimension' => 'traffic_source',
                    'items' => [
                        ['key' => 'direct', 'label' => 'مباشر', 'count' => $directViews],
                        ['key' => 'registered', 'label' => 'مستخدمون مسجّلون', 'count' => $registeredViews],
                        ['key' => 'sponsored', 'label' => 'إعلانات الرعاية', 'count' => $sponsoredViews],
                    ],
                    'total' => $directViews + $registeredViews + $sponsoredViews,
                ];

                // 5) Days on market
                $daysOnMarket = (int) $property->created_at->diffInDays(now());

                // 6) Reviews list
                $reviews = Review::where('property_id', $property->id)
                    ->with('reviewer:id,name')
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'rating' => $r->rating,
                        'comment' => $r->comment,
                        'reviewer_name' => $r->reviewer?->name,
                        'created_at' => $r->created_at?->format('Y-m-d H:i:s'),
                    ])
                    ->toArray();

                // 7) Competitor properties
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
                    ->map(fn ($p) => [
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
                    'generated_at' => now()->toIso8601String(),
                    'kpis' => $kpis,
                    'charts' => [
                        [
                            'metric' => 'views',
                            'unit' => 'count',
                            'points' => $points,
                        ],
                    ],
                    'funnel' => $funnel,
                    'distributions' => [
                        'traffic_sources' => $trafficSources,
                    ],
                    'meta' => [
                        'days_on_market' => $daysOnMarket,
                    ],
                    'lists' => [
                        'price_history' => [],
                        'reviews' => [
                            'title' => 'التقييمات',
                            'items' => $reviews,
                        ],
                        'competitors' => [
                            'title' => 'العقارات المنافسة',
                            'items' => $competitors,
                            'sort_by' => 'views',
                        ],
                    ],
                ];
            }
        );
    }

    public function clearCache(): void
    {
        $this->cache->flushScope('property');
    }
}
