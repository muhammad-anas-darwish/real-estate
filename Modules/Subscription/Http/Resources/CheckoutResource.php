<?php

namespace Modules\Subscription\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class CheckoutResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [];
    }

    protected function getCustomData(): array
    {
        return [
            'checkout_session_id' => $this->resource['checkout_session_id'] ?? null,
            'checkout_url' => $this->resource['checkout_url'] ?? null,
            'subscription_id' => $this->resource['subscription_id'] ?? null,
            'plan' => $this->when(isset($this->resource['plan']), function () {
                return SubscriptionPlanResource::make($this->resource['plan']);
            }),
            'pricing' => $this->resource['pricing'] ?? null,
            'discount' => $this->when(isset($this->resource['discount']), function () {
                if ($this->resource['discount'] === null) {
                    return null;
                }

                return SubscriptionDiscountResource::make($this->resource['discount']);
            }),
        ];
    }
}
