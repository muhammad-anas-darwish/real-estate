<?php

namespace Modules\Subscription\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class SubscriptionResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'plan' => SubscriptionPlanResource::class,
            'discount' => SubscriptionDiscountResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'user_id' => $this->user_id,
            'plan_id' => $this->plan_id,
            'discount_id' => $this->discount_id,
            'status' => $this->status,
            'starts_at' => $this->formatDate($this->starts_at),
            'ends_at' => $this->formatDate($this->ends_at),
            'cancelled_at' => $this->formatDate($this->cancelled_at),
            'currency' => $this->currency,
        ];
    }
}
