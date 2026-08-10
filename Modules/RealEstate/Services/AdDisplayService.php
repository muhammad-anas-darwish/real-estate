<?php

namespace Modules\RealEstate\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\PropertyStatus;

class AdDisplayService
{
    public function __construct(
        protected AdService $adService
    ) {}

    public function getAdsForDisplay(int $groupId, ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::today();

        $eligibleAds = $this->adService->getEligibleAds($groupId, $date)
            ->filter(fn (Ad $ad) => $this->isPropertyAvailable($ad));

        if ($eligibleAds->isNotEmpty()) {
            return $eligibleAds->values();
        }

        $defaultAd = Ad::where('ad_group_id', $groupId)
            ->where('is_default', true)
            ->where('status', AdStatus::ACTIVE)
            ->with(['media', 'property'])
            ->get()
            ->first(fn (Ad $ad) => $this->isPropertyAvailable($ad));

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
            ->get()
            ->filter(fn (Ad $ad) => $this->isPropertyAvailable($ad))
            ->values();
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
            ->get()
            ->filter(fn (Ad $ad) => $this->isPropertyAvailable($ad));

        $standaloneAds = $this->getStandaloneAdsForDisplay($date);

        return $groupAds->merge($standaloneAds)->values();
    }

    private function isPropertyAvailable(Ad $ad): bool
    {
        if ($ad->property_id === null) {
            return true;
        }

        $property = $ad->property;

        if (! $property) {
            return true;
        }

        return $property->status === PropertyStatus::APPROVED;
    }
}
