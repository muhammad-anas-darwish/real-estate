<?php

namespace Modules\RealEstate\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Enums\AdStatus;

class AdDisplayService
{
    public function __construct(
        protected AdService $adService
    ) {}

    public function getAdsForDisplay(int $groupId, ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::today();

        $eligibleAds = $this->adService->getEligibleAds($groupId, $date);

        if ($eligibleAds->isNotEmpty()) {
            return $eligibleAds;
        }

        $defaultAd = Ad::where('ad_group_id', $groupId)
            ->where('is_default', true)
            ->where('status', AdStatus::ACTIVE)
            ->with(['media', 'property'])
            ->first();

        if ($defaultAd) {
            return collect([$defaultAd]);
        }

        return collect();
    }

    public function getStandaloneAdsForDisplay(?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::today();
        $dateStr = $date->format('Y-m-d');

        return Ad::whereNull('ad_group_id')
            ->where('status', AdStatus::ACTIVE)
            ->where('start_date', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $dateStr);
            })
            ->with(['media', 'property'])
            ->get();
    }

    public function getAvailableAds(?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::today();
        $dateStr = $date->format('Y-m-d');

        $activeGroups = AdGroup::active()->pluck('id');

        $groupAds = Ad::whereIn('ad_group_id', $activeGroups)
            ->where('status', AdStatus::ACTIVE)
            ->where('start_date', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $dateStr);
            })
            ->with(['adGroup', 'media', 'property'])
            ->get();

        $standaloneAds = $this->getStandaloneAdsForDisplay($date);

        return $groupAds->merge($standaloneAds);
    }
}
