<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Subscription\Http\Resources\PublicPlanResource;
use Modules\Subscription\Services\PlanService;

class PublicPlanController extends Controller
{
    public function __construct(
        protected readonly PlanService $planService
    ) {}

    public function index()
    {
        $plans = $this->planService->allActive();

        return $this->successResponse(PublicPlanResource::collection($plans));
    }

    public function show($id)
    {
        $plan = $this->planService->find((int) $id);

        if (! $plan->is_active) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(PublicPlanResource::make($plan));
    }
}
