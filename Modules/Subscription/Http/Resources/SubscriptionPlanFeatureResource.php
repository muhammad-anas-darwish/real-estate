<?php

namespace Modules\Subscription\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class SubscriptionPlanFeatureResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'plan' => SubscriptionPlanResource::class,
            'feature' => SubscriptionFeatureResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'plan_id' => $this->plan_id,
            'feature_id' => $this->feature_id,
            'is_enabled' => $this->is_enabled,
            'limit_value' => $this->limit_value,
        ];
    }
}
