<?php

namespace Modules\Subscription\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class SubscriptionDiscountResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'plan' => SubscriptionPlanResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'plan_id' => $this->plan_id,
            'max_uses' => $this->max_uses,
            'used_count' => $this->used_count,
            'expires_at' => $this->formatDate($this->expires_at),
            'is_active' => $this->is_active,
            'is_valid' => $this->isValid(),
        ];
    }
}
