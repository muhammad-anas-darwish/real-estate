<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Statistics\Services\AdminStatsService;
use Modules\Statistics\Services\PeriodResolver;

class AdminDashboardController extends Controller
{
    use ApiResponses, ApplyPermissions, ValidatesRequests;

    public function __construct(
        protected readonly AdminStatsService $stats,
        protected readonly PeriodResolver $periodResolver
    ) {
        $this->applyPermissions(
            'admin_statistics',
            [],
            [
                'overview' => 'view',
                'properties' => 'view',
                'crm' => 'view',
                'ads' => 'view',
                'subscriptions' => 'view',
                'moderation' => 'view',
                'communication' => 'view',
            ]
        );
    }

    public function overview(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->overview($this->periodResolver->fromRequest($request))
        );
    }

    public function properties(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->properties($this->periodResolver->fromRequest($request))
        );
    }

    public function crm(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->crm($this->periodResolver->fromRequest($request))
        );
    }

    public function ads(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->ads($this->periodResolver->fromRequest($request))
        );
    }

    public function subscriptions(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->subscriptions($this->periodResolver->fromRequest($request))
        );
    }

    public function moderation(): JsonResponse
    {
        return $this->successResponse($this->stats->moderation());
    }

    public function communication(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->communication($this->periodResolver->fromRequest($request))
        );
    }
}
