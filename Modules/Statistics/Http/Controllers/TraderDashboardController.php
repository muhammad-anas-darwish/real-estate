<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Statistics\Services\PeriodResolver;
use Modules\Statistics\Services\TraderStatsService;

class TraderDashboardController extends Controller
{
    use ApiResponses, ApplyPermissions, ValidatesRequests;

    public function __construct(
        protected readonly TraderStatsService $stats,
        protected readonly PeriodResolver $periodResolver
    ) {
        $this->applyPermissions(
            'statistics',
            [],
            [
                'summary' => 'view',
                'viewsTrend' => 'view',
                'leadsByStatus' => 'view',
                'propertiesByStatus' => 'view',
                'topProperties' => 'view',
                'recentLeads' => 'view',
                'upcomingAppointments' => 'view',
                'expiringRentals' => 'view',
                'sponsoredAdsSummary' => 'view',
                'exportProperties' => 'export',
            ]
        );
    }

    public function summary(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);
        $data = $this->stats->summary(auth()->id(), $filter);

        return $this->successResponse($data);
    }

    public function viewsTrend(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);

        return $this->successResponse(
            $this->stats->viewsTrend(auth()->id(), $filter)
        );
    }

    public function leadsByStatus(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);

        return $this->successResponse(
            $this->stats->leadsByStatus(auth()->id(), $filter)
        );
    }

    public function propertiesByStatus(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->propertiesByStatus(auth()->id(), $this->periodResolver->fromRequest($request))
        );
    }

    public function topProperties(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);
        $limit = (int) $request->input('limit', 10);

        return $this->successResponse(
            $this->stats->topProperties(auth()->id(), $filter, $limit)
        );
    }

    public function recentLeads(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);

        return $this->successResponse(
            $this->stats->recentLeads(auth()->id(), $limit)
        );
    }

    public function upcomingAppointments(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 7);

        return $this->successResponse(
            $this->stats->upcomingAppointments(auth()->id(), $days)
        );
    }

    public function expiringRentals(Request $request): JsonResponse
    {
        $within = (int) $request->input('within_days', 30);

        return $this->successResponse(
            $this->stats->expiringRentals(auth()->id(), $within)
        );
    }

    public function sponsoredAdsSummary(Request $request): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);

        return $this->successResponse(
            $this->stats->sponsoredAdsSummary(auth()->id(), $filter)
        );
    }

    public function exportProperties(Request $request)
    {
        $filter = $this->periodResolver->fromRequest($request);
        $rows = $this->stats->exportProperties(auth()->id(), $filter);

        $filename = 'trader-properties-'.now()->format('Ymd-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID', 'Name', 'Status', 'Price', 'Currency',
                'Total Views', 'Views In Range', 'Favorites', 'Created At',
            ]);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
