<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Subscription\DTOs\SubscriptionPlanFeatureDTO;
use Modules\Subscription\Http\Requests\StorePlanFeatureRequest;
use Modules\Subscription\Http\Requests\UpdatePlanFeatureRequest;
use Modules\Subscription\Http\Resources\SubscriptionPlanFeatureResource;
use Modules\Subscription\Services\PlanFeatureService;

class PlanFeatureController extends Controller
{
    public function __construct(
        protected readonly PlanFeatureService $planFeatureService
    ) {
        $this->applyPermissions(
            'subscription_plan_features',
            ['index', 'show', 'store', 'update', 'destroy']
        );
    }

    public function store(StorePlanFeatureRequest $request)
    {
        $dto = SubscriptionPlanFeatureDTO::fromRequest($request->validated());
        $planFeature = $this->planFeatureService->store($dto);

        return $this->successResponse(SubscriptionPlanFeatureResource::make($planFeature))->created('subscription_plan_feature');
    }

    public function update(UpdatePlanFeatureRequest $request, $id)
    {
        $dto = SubscriptionPlanFeatureDTO::fromRequest($request->validated());
        $planFeature = $this->planFeatureService->update((int) $id, $dto);

        return $this->successResponse(SubscriptionPlanFeatureResource::make($planFeature))->updated('subscription_plan_feature');
    }

    public function destroy($id)
    {
        $this->planFeatureService->destroy((int) $id);

        return $this->successResponse()->deleted('subscription_plan_feature');
    }
}
