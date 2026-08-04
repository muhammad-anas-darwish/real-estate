<?php

namespace Modules\ServiceProvider\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\RealEstate\Http\Resources\PropertyResource;

class ServiceRequestResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'client' => UserResource::class,
            'provider' => ServiceProviderResource::class,
            'property' => PropertyResource::class,
            'tasks' => ServiceRequestTaskResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'client_id' => $this->client_id,
            'provider_id' => $this->provider_id,
            'property_id' => $this->property_id,
            'service_type' => $this->service_type,
            'status' => $this->status,
            'scheduled_at' => $this->formatDate($this->scheduled_at),
            'completed_at' => $this->formatDate($this->completed_at),
            'cancelled_at' => $this->formatDate($this->cancelled_at),
            'client_notes' => $this->client_notes,
            'provider_notes' => $this->provider_notes,
            'admin_notes' => $this->admin_notes,
            'price' => $this->price,
            'platform_fee' => $this->platform_fee,
            'provider_earnings' => $this->provider_earnings,
            'is_paid' => $this->is_paid,
            'is_provider_paid' => $this->is_provider_paid,
        ];
    }
}
