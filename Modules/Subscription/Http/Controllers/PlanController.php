<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Subscription\DTOs\SubscriptionPlanDTO;
use Modules\Subscription\Http\Requests\StorePlanRequest;
use Modules\Subscription\Http\Requests\SyncPlanFeaturesRequest;
use Modules\Subscription\Http\Requests\UpdatePlanRequest;
use Modules\Subscription\Http\Resources\SubscriptionPlanResource;
use Modules\Subscription\Services\PlanService;

class PlanController extends Controller
{
    public function __construct(
        protected readonly PlanService $planService
    ) {
        $this->applyPermissions(
            'subscription_plans',
            ['index', 'show', 'store', 'update', 'destroy']
        );
    }

    public function index()
    {
        $plans = $this->planService->all();

        return $this->paginatedResponse(SubscriptionPlanResource::collection($plans));
    }

    public function show($id)
    {
        $plan = $this->planService->find((int) $id);

        return $this->successResponse(SubscriptionPlanResource::make($plan));
    }

    public function store(StorePlanRequest $request)
    {
        $dto = SubscriptionPlanDTO::fromRequest($request->validated());
        $plan = $this->planService->store($dto);

        return $this->successResponse(SubscriptionPlanResource::make($plan))->created('subscription_plan');
    }

    public function update(UpdatePlanRequest $request, $id)
    {
        $dto = SubscriptionPlanDTO::fromRequest($request->validated());
        $plan = $this->planService->update((int) $id, $dto);

        return $this->successResponse(SubscriptionPlanResource::make($plan))->updated('subscription_plan');
    }

    public function destroy($id)
    {
        $this->planService->destroy((int) $id);

        return $this->successResponse()->deleted('subscription_plan');
    }

    public function syncFeatures(SyncPlanFeaturesRequest $request, $id)
    {
        $plan = $this->planService->syncFeatures((int) $id, $request->validated('features'));

        return $this->successResponse(SubscriptionPlanResource::make($plan))->updated('subscription_plan');
    }
}
