<?php

namespace Modules\Subscription\Services;

use App\Services\BaseService;
use Modules\Subscription\DTOs\SubscriptionPlanFeatureDTO;
use Modules\Subscription\Entities\SubscriptionPlanFeature;

class PlanFeatureService extends BaseService
{
    const CACHE_TAG = 'subscription-plan-features';

    public function store(SubscriptionPlanFeatureDTO $dto): SubscriptionPlanFeature
    {
        $planFeature = SubscriptionPlanFeature::create($dto->toArray());
        $this->clearCache();

        return $planFeature->load(['plan', 'feature']);
    }

    public function update(int $id, SubscriptionPlanFeatureDTO $dto): SubscriptionPlanFeature
    {
        $planFeature = SubscriptionPlanFeature::findOrFail($id);
        $planFeature->update($dto->toArray());
        $this->clearCache();

        return $planFeature->fresh(['plan', 'feature']);
    }

    public function destroy(int $id): void
    {
        $planFeature = SubscriptionPlanFeature::findOrFail($id);
        $planFeature->delete();
        $this->clearCache();
    }
}
