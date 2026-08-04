<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\AdTrackingService;

class AdTrackingController extends Controller
{
    public function __construct(
        protected readonly AdTrackingService $adTrackingService
    ) {}

    public function recordView(int $id, Request $request)
    {
        $this->adTrackingService->recordView(
            $id,
            $request->user()?->id,
            $request->ip(),
            $request->userAgent()
        );

        return $this->successResponse([], 'View recorded');
    }

    public function recordVisit(int $id, Request $request)
    {
        $this->adTrackingService->recordVisit(
            $id,
            $request->user()?->id,
            $request->ip(),
            $request->userAgent()
        );

        return $this->successResponse([], 'Visit recorded');
    }
}
