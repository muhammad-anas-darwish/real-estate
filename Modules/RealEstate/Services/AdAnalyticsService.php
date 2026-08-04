<?php

namespace Modules\RealEstate\Services;

use Carbon\Carbon;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\AdVisit;
use Modules\RealEstate\Enums\AdStatus;

class AdAnalyticsService
{
    public function getDashboardData(array $filters): array
    {
        $query = Ad::query()->filter();

        $totalAds = (clone $query)->count();
        $activeAds = (clone $query)->where('status', AdStatus::ACTIVE)->count();

        $adIds = (clone $query)->pluck('id');

        $totalViews = AdView::whereIn('ad_id', $adIds)->count();
        $totalVisits = AdVisit::whereIn('ad_id', $adIds)->count();
        $ctr = $totalViews > 0 ? round(($totalVisits / $totalViews) * 100, 2) : 0.0;

        return [
            'total_ads' => $totalAds,
            'active_ads' => $activeAds,
            'total_views' => $totalViews,
            'total_visits' => $totalVisits,
            'ctr' => $ctr,
        ];
    }

    public function getGroupAnalytics(int $groupId, array $filters): array
    {
        $group = AdGroup::withTrashed()->findOrFail($groupId);

        $ads = Ad::where('ad_group_id', $groupId);

        $totalAds = (clone $ads)->count();
        $activeAds = (clone $ads)->where('status', AdStatus::ACTIVE)->count();
        $adIds = (clone $ads)->pluck('id');

        $totalViews = AdView::whereIn('ad_id', $adIds)->count();
        $totalVisits = AdVisit::whereIn('ad_id', $adIds)->count();
        $ctr = $totalViews > 0 ? round(($totalVisits / $totalViews) * 100, 2) : 0.0;

        $defaultAd = $group->ads()->where('is_default', true)->first();

        return [
            'group_id' => $groupId,
            'group_name' => $group->name,
            'total_ads' => $totalAds,
            'active_ads' => $activeAds,
            'total_views' => $totalViews,
            'total_visits' => $totalVisits,
            'ctr' => $ctr,
            'default_ad' => $defaultAd ? [
                'id' => $defaultAd->id,
                'title' => $defaultAd->title,
            ] : null,
        ];
    }

    public function getTrendData(array $filters, string $period = 'day'): array
    {
        $query = Ad::query()->filter();
        $adIds = $query->pluck('id');

        $from = isset($filters['from'])
            ? Carbon::parse($filters['from'])
            : Carbon::today()->subDays(30);

        $to = isset($filters['to'])
            ? Carbon::parse($filters['to'])->endOfDay()
            : Carbon::today()->endOfDay();

        $views = AdView::whereIn('ad_id', $adIds)
            ->whereBetween('viewed_at', [$from, $to])
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $visits = AdVisit::whereIn('ad_id', $adIds)
            ->whereBetween('visited_at', [$from, $to])
            ->selectRaw('DATE(visited_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $timeSeries = collect();
        $current = $from->copy();

        while ($current->lte($to)) {
            $date = $current->format('Y-m-d');
            $timeSeries->push([
                'date' => $date,
                'views' => (int) ($views[$date] ?? 0),
                'visits' => (int) ($visits[$date] ?? 0),
                'ctr' => 0.0,
            ]);
            $current->addDay();
        }

        $timeSeries = $timeSeries->map(function ($item) {
            $item['ctr'] = $item['views'] > 0
                ? round(($item['visits'] / $item['views']) * 100, 2)
                : 0.0;

            return $item;
        });

        return [
            'period' => $period,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'time_series' => $timeSeries,
        ];
    }

    public function exportReport(array $filters): array
    {
        $query = Ad::query()->filter();
        $adIds = $query->pluck('id');

        $from = isset($filters['from'])
            ? Carbon::parse($filters['from'])
            : Carbon::today()->subDays(30);

        $to = isset($filters['to'])
            ? Carbon::parse($filters['to'])->endOfDay()
            : Carbon::today()->endOfDay();

        $ads = Ad::whereIn('id', $adIds)
            ->with(['adGroup', 'creator'])
            ->get();

        $data = $ads->map(function ($ad) use ($from, $to) {
            $views = AdView::where('ad_id', $ad->id)
                ->whereBetween('viewed_at', [$from, $to])
                ->count();

            $visits = AdVisit::where('ad_id', $ad->id)
                ->whereBetween('visited_at', [$from, $to])
                ->count();

            return [
                'ad_id' => $ad->id,
                'title' => $ad->title,
                'group' => $ad->adGroup?->name,
                'status' => $ad->status?->value,
                'media_type' => $ad->media_type?->value,
                'created_by' => $ad->creator?->name,
                'views' => $views,
                'visits' => $visits,
                'ctr' => $views > 0 ? round(($visits / $views) * 100, 2) : 0.0,
                'start_date' => $ad->start_date?->format('Y-m-d'),
                'end_date' => $ad->end_date?->format('Y-m-d'),
            ];
        });

        return [
            'generated_at' => now()->toDateTimeString(),
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'total_ads' => $data->count(),
            'report' => $data,
        ];
    }
}
