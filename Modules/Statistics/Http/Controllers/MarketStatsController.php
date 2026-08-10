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
