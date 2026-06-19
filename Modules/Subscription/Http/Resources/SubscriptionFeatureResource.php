<?php

namespace Modules\Subscription\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class SubscriptionFeatureResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
            'pivot' => $this->whenPivotLoaded('subscription_plan_features', function () {
                return [
                    'is_enabled' => $this->pivot->is_enabled,
                    'limit_value' => $this->pivot->limit_value,
                ];
            }),
        ];
    }
}
