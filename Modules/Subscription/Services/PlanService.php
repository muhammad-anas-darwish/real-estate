<?php

namespace Modules\Subscription\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Subscription\DTOs\SubscriptionPlanDTO;
use Modules\Subscription\Entities\SubscriptionPlan;

class PlanService extends BaseService
{
    const CACHE_TAG = 'subscription-plans';

    public function all(): LengthAwarePaginator
    {
        return SubscriptionPlan::query()
            ->filter()
            ->with(['features'])
            ->ordered()
            ->paginate($this->getPerPage());
    }

    public function active(): LengthAwarePaginator
    {
        return SubscriptionPlan::query()
            ->active()
            ->with(['features'])
            ->ordered()
            ->paginate($this->getPerPage());
    }

    public function allActive(): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPlan::query()
            ->active()
            ->with(['features'])
            ->ordered()
            ->get();
    }

    public function find(int $id): SubscriptionPlan
    {
        return SubscriptionPlan::with(['features'])->findOrFail($id);
    }

    public function store(SubscriptionPlanDTO $dto): SubscriptionPlan
    {
        $plan = SubscriptionPlan::create($dto->toArray());
        $this->clearCache();

        return $plan->fresh(['features']);
    }

    public function update(int $id, SubscriptionPlanDTO $dto): SubscriptionPlan
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update($dto->toArray());
        $this->clearCache();

        return $plan->fresh(['features']);
    }

    public function destroy(int $id): void
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->delete();
        $this->clearCache();
    }

    public function syncFeatures(int $planId, array $features): SubscriptionPlan
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        $syncData = [];
        foreach ($features as $feature) {
            $syncData[$feature['feature_id']] = [
                'is_enabled' => $feature['is_enabled'] ?? true,
                'limit_value' => $feature['limit_value'] ?? null,
            ];
        }

        $plan->features()->sync($syncData);
        $this->clearCache();

        return $plan->fresh(['features']);
    }
}
