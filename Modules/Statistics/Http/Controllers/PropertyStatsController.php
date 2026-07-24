<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Statistics\Services\PeriodResolver;
use Modules\Statistics\Services\PropertyStatsService;

class PropertyStatsController extends Controller
{
    use ApiResponses, ApplyPermissions, ValidatesRequests;

    public function __construct(
        protected readonly PropertyStatsService $stats,
        protected readonly PeriodResolver $periodResolver
    ) {
        $this->applyPermissions(
            'statistics',
            [],
            [
                'show' => 'view',
            ]
        );
    }

    public function show(Request $request, int $property): JsonResponse
    {
        $filter = $this->periodResolver->fromRequest($request);
        $data = $this->stats->show($property, $filter);

        return $this->successResponse($data);
    }
}
