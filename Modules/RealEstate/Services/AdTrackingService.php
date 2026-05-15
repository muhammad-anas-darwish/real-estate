<?php

namespace Modules\RealEstate\Services;

use Carbon\Carbon;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\AdVisit;

class AdTrackingService
{
    public function recordView(int $adId, ?int $userId = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        AdView::create([
            'ad_id' => $adId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'viewed_at' => now(),
        ]);
    }

    public function recordVisit(int $adId, ?int $userId = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $ad = Ad::findOrFail($adId);

        if (empty($ad->external_url)) {
            return;
        }

        AdVisit::create([
            'ad_id' => $adId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'visited_at' => now(),
        ]);
    }

    public function getViewCount(int $adId): int
    {
        return AdView::where('ad_id', $adId)->count();
    }

    public function getVisitCount(int $adId): int
    {
        return AdVisit::where('ad_id', $adId)->count();
    }

    public function getClickThroughRate(int $adId): float
    {
        $views = $this->getViewCount($adId);
        $visits = $this->getVisitCount($adId);

        if ($views === 0) {
            return 0.0;
        }

        return round(($visits / $views) * 100, 2);
    }

    public function getAnalytics(int $adId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? Carbon::today()->subDays(30);
        $to = $to ?? Carbon::today()->endOfDay();

        $views = AdView::where('ad_id', $adId)
            ->whereBetween('viewed_at', [$from, $to])
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $visits = AdVisit::where('ad_id', $adId)
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
            ]);
            $current->addDay();
        }

        return [
            'ad_id' => $adId,
            'total_views' => $views->sum(),
            'total_visits' => $visits->sum(),
            'ctr' => $this->getClickThroughRate($adId),
            'time_series' => $timeSeries,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ];
    }
}
