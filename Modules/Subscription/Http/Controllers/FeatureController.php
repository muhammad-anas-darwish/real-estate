<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Subscription\DTOs\SubscriptionFeatureDTO;
use Modules\Subscription\Http\Requests\StoreFeatureRequest;
use Modules\Subscription\Http\Requests\UpdateFeatureRequest;
use Modules\Subscription\Http\Resources\SubscriptionFeatureResource;
use Modules\Subscription\Services\FeatureService;

class FeatureController extends Controller
{
    public function __construct(
        protected readonly FeatureService $featureService
    ) {
        $this->applyPermissions(
            'subscription_features',
            ['index', 'show', 'store', 'update', 'destroy']
        );
    }

    public function index()
    {
        $features = $this->featureService->all();

        return $this->paginatedResponse(SubscriptionFeatureResource::collection($features));
    }

    public function show($id)
    {
        $feature = $this->featureService->find((int) $id);

        return $this->successResponse(SubscriptionFeatureResource::make($feature));
    }

    public function store(StoreFeatureRequest $request)
    {
        $dto = SubscriptionFeatureDTO::fromRequest($request->validated());
        $feature = $this->featureService->store($dto);

        return $this->successResponse(SubscriptionFeatureResource::make($feature))->created('subscription_feature');
    }

    public function update(UpdateFeatureRequest $request, $id)
    {
        $dto = SubscriptionFeatureDTO::fromRequest($request->validated());
        $feature = $this->featureService->update((int) $id, $dto);

        return $this->successResponse(SubscriptionFeatureResource::make($feature))->updated('subscription_feature');
    }

    public function destroy($id)
    {
        $this->featureService->destroy((int) $id);

        return $this->successResponse()->deleted('subscription_feature');
    }
}
