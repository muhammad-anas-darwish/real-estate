<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Crm\Services\LeadStatsService;
use Modules\RealEstate\Http\Resources\AppointmentResource;

class LeadDashboardController extends Controller
{
    public function __construct(
        protected readonly LeadStatsService $statsService
    ) {
        $this->applyPermissions(
            'crm_dashboard',
            [],
            [
                'summary' => 'view',
                'today' => 'view',
            ]
        );
    }

    public function summary()
    {
        $summary = $this->statsService->summary(auth()->id());

        return $this->successResponse($summary);
    }

    public function today()
    {
        $data = $this->statsService->today(auth()->id());

        return $this->successResponse([
            'today_appointments' => AppointmentResource::collection($data['today_appointments']),
            'overdue_appointments' => AppointmentResource::collection($data['overdue_appointments']),
        ]);
    }
}
