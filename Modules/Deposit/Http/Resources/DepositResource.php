<?php

namespace Modules\Deposit\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\RealEstate\Http\Resources\PropertyResource;

class DepositResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'property' => PropertyResource::class,
            'buyer' => UserResource::class,
            'seller' => UserResource::class,
            'cancelledBy' => UserResource::class,
            'releasedBy' => UserResource::class,
            'refundedBy' => UserResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'reference_number' => $this->reference_number,
            'property_id' => $this->property_id,
            'buyer_id' => $this->buyer_id,
            'seller_id' => $this->seller_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'terms' => $this->terms,
            'notes' => $this->notes,
            'held_at' => $this->held_at?->format('Y-m-d H:i:s'),
            'released_at' => $this->released_at?->format('Y-m-d H:i:s'),
            'refunded_at' => $this->refunded_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancelled_by' => $this->cancelled_by,
            'released_by' => $this->released_by,
            'refunded_by' => $this->refunded_by,
            'cancellation_reason' => $this->cancellation_reason,
            'release_notes' => $this->release_notes,
            'refund_notes' => $this->refund_notes,
        ];
    }
}
