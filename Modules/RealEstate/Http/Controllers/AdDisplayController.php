<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RealEstate\DTOs\AdDisplayDTO;
use Modules\RealEstate\Services\AdDisplayService;

class AdDisplayController extends Controller
{
    public function __construct(
        protected readonly AdDisplayService $adDisplayService
    ) {}

    public function display($groupId)
    {
        $ads = $this->adDisplayService->getAdsForDisplay((int) $groupId);
        $data = $ads->map(fn ($ad) => AdDisplayDTO::fromAd($ad)->toArray());

        return $this->successResponse($data);
    }

    public function displayStandalone()
    {
        $ads = $this->adDisplayService->getStandaloneAdsForDisplay();
        $data = $ads->map(fn ($ad) => AdDisplayDTO::fromAd($ad)->toArray());

        return $this->successResponse($data);
    }

    public function displayAll()
    {
        $ads = $this->adDisplayService->getAvailableAds();
        $data = $ads->map(fn ($ad) => AdDisplayDTO::fromAd($ad)->toArray());

        return $this->successResponse($data);
    }
}
