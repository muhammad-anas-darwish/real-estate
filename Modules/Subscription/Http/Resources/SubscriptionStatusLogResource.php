<?php

namespace Modules\Subscription\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class SubscriptionStatusLogResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [];
    }

    protected function getCustomData(): array
    {
        return [
            'subscription_id' => $this->subscription_id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'reason' => $this->reason,
        ];
    }
}
