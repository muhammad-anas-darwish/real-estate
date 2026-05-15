<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Http\Requests\AdAnalyticsRequest;
use Modules\RealEstate\Services\AdAnalyticsService;
use Modules\RealEstate\Services\AdTrackingService;

class AdAnalyticsController extends Controller
{
    public function __construct(
        protected readonly AdAnalyticsService $adAnalyticsService,
        protected readonly AdTrackingService $adTrackingService
    ) {}

    public function dashboard(AdAnalyticsRequest $request)
    {
        $user = Auth::user();
        $filters = $request->validated();

        if (! $user->hasRole('super-admin')) {
            $filters['created_by'] = $user->id;
        }

        $data = $this->adAnalyticsService->getDashboardData($filters);

        return $this->successResponse($data);
    }

    public function groupAnalytics(AdAnalyticsRequest $request, $groupId)
    {
        $group = AdGroup::withTrashed()->findOrFail($groupId);

        $user = Auth::user();
        if (! $user->hasRole('super-admin') && $group->created_by !== $user->id) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'data' => null,
                ], 403)
            );
        }

        $data = $this->adAnalyticsService->getGroupAnalytics((int) $groupId, $request->validated());

        return $this->successResponse($data);
    }

    public function adAnalytics(AdAnalyticsRequest $request, $adId)
    {
        $ad = Ad::withTrashed()->findOrFail($adId);

        $user = Auth::user();
        if (! $user->hasRole('super-admin') && $ad->created_by !== $user->id) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'data' => null,
                ], 403)
            );
        }

        $filters = $request->validated();
        $from = isset($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from']) : null;
        $to = isset($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to']) : null;

        $data = $this->adTrackingService->getAnalytics((int) $adId, $from, $to);

        return $this->successResponse($data);
    }

    public function export(AdAnalyticsRequest $request)
    {
        $user = Auth::user();
        $filters = $request->validated();

        if (! $user->hasRole('super-admin')) {
            $filters['created_by'] = $user->id;
        }

        $data = $this->adAnalyticsService->exportReport($filters);

        return $this->successResponse($data);
    }
}
