<?php

namespace Modules\Statistics\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\Statistics\DTOs\StatsFilterDTO;

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
            fn () => $this->buildOverview($filter)
        );
    }

    public function byCity(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'market',
            'by-city',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildByCity($filter)
        );
    }

    public function byCategory(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'market',
            'by-category',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildByCategory($filter)
        );
    }

    public function byPriceRange(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'market',
            'by-price-range',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildByPriceRange($filter)
        );
    }

    public function topViewed(StatsFilterDTO $filter, int $limit = 10): array
    {
        return $this->cache->remember(
            'market',
            'top-viewed',
            ['f' => $filter->toArray(), 'limit' => $limit],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildTopViewed($filter, $limit)
        );
    }

    public function topSaved(StatsFilterDTO $filter, int $limit = 10): array
    {
        return $this->cache->remember(
            'market',
            'top-saved',
            ['f' => $filter->toArray(), 'limit' => $limit],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildTopSaved($filter, $limit)
        );
    }

    public function listingsTrend(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'market',
            'listings-trend',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildListingsTrend($filter)
        );
    }

    public function clearCache(): void
    {
        $this->cache->flushScope('market');
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
            'generated_at' => now()->toIso8601String(),
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

        $items = $rows->map(fn ($r) => [
            'city_id' => $r->city_id,
            'city_name' => $r->city?->name ?? 'غير محدد',
            'count' => (int) $r->count,
            'avg_price' => round((float) $r->avg_price, 2),
            'avg_area' => round((float) $r->avg_area, 2),
        ])->toArray();

        return [
            'dimension' => 'city',
            'items' => $items,
            'total' => array_sum(array_column($items, 'count')),
        ];
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

        return [
            'dimension' => 'category',
            'items' => $items,
            'total' => $total,
        ];
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

        return [
            'dimension' => 'price_range',
            'items' => $items,
            'total' => $total,
        ];
    }

    protected function buildTopViewed(StatsFilterDTO $filter, int $limit): array
    {
        $properties = $this->baseQuery($filter)
            ->orderByDesc('views')
            ->limit($limit)
            ->get(['id', 'name', 'price', 'currency', 'views', 'city_id'])
            ->load('city:id,name');

        $items = $properties->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'city' => $p->city?->name,
            'price' => (float) $p->price,
            'currency' => $p->currency,
            'views' => (int) $p->views,
        ])->toArray();

        return [
            'title' => 'أكثر 10 عقارات مشاهدة',
            'items' => $items,
            'sort_by' => 'views',
        ];
    }

    protected function buildTopSaved(StatsFilterDTO $filter, int $limit): array
    {
        $properties = $this->baseQuery($filter)
            ->withCount('favoritedBy as favorites_count')
            ->orderByDesc('favorites_count')
            ->limit($limit)
            ->get(['id', 'name', 'price', 'currency', 'city_id'])
            ->load('city:id,name');

        $items = $properties->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'city' => $p->city?->name,
            'price' => (float) $p->price,
            'currency' => $p->currency,
            'favorites_count' => (int) $p->favorites_count,
        ])->toArray();

        return [
            'title' => 'أكثر 10 عقارات حجوزاً في المفضلات',
            'items' => $items,
            'sort_by' => 'favorites_count',
        ];
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

        return [
            'metric' => 'listings',
            'unit' => 'count',
            'points' => $points,
        ];
    }
}
