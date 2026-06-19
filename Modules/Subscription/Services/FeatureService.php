<?php

namespace Modules\Subscription\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Subscription\DTOs\SubscriptionFeatureDTO;
use Modules\Subscription\Entities\SubscriptionFeature;

class FeatureService extends BaseService
{
    const CACHE_TAG = 'subscription-features';

    public function all(): LengthAwarePaginator
    {
        return SubscriptionFeature::query()
            ->filter()
            ->paginate($this->getPerPage());
    }

    public function find(int $id): SubscriptionFeature
    {
        return SubscriptionFeature::findOrFail($id);
    }

    public function store(SubscriptionFeatureDTO $dto): SubscriptionFeature
    {
        $feature = SubscriptionFeature::create($dto->toArray());
        $this->clearCache();

        return $feature;
    }

    public function update(int $id, SubscriptionFeatureDTO $dto): SubscriptionFeature
    {
        $feature = SubscriptionFeature::findOrFail($id);
        $feature->update($dto->toArray());
        $this->clearCache();

        return $feature;
    }

    public function destroy(int $id): void
    {
        $feature = SubscriptionFeature::findOrFail($id);
        $feature->delete();
        $this->clearCache();
    }
}
