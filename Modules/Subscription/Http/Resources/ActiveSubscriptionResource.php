<?php

namespace Modules\Subscription\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class ActiveSubscriptionResource extends BaseJsonResource
{
    protected array $baseFields = [];

    protected function getRelationMap(): array
    {
        return [
            'plan' => SubscriptionPlanResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        $subscription = $this->resource['subscription'];
        $daysRemaining = null;

        if ($subscription->ends_at) {
            $diff = now()->diffInDays($subscription->ends_at, false);
            $daysRemaining = $diff > 0 ? (int) $diff : 0;
        }

        return [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'starts_at' => $this->formatDate($subscription->starts_at),
            'ends_at' => $this->formatDate($subscription->ends_at),
            'days_remaining' => $daysRemaining,
            'currency' => $subscription->currency,
            'plan' => $this->resource['plan'] ? SubscriptionPlanResource::make($this->resource['plan']) : null,
            'features' => $this->resource['features'],
        ];
    }

    protected function getIncludedRelations(): array
    {
        return [];
    }
}
