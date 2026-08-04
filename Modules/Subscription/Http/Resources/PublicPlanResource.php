<?php

namespace Modules\Subscription\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class PublicPlanResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'features' => SubscriptionFeatureResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'duration_days' => $this->duration_days,
        ];
    }
}
