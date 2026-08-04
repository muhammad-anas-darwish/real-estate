<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\RealEstate\Http\Resources\PropertyResource;

class PropertyViewingResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'property' => PropertyResource::class,
            'user' => UserResource::class,
            'agent' => UserResource::class,
            'cancelledBy' => UserResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'property_id' => $this->property_id,
            'user_id' => $this->user_id,
            'agent_id' => $this->agent_id,
            'scheduled_at' => $this->formatDate($this->scheduled_at),
            'duration_minutes' => $this->duration_minutes,
            'buffer_minutes' => $this->buffer_minutes,
            'status' => $this->status,
            'viewing_type' => $this->viewing_type,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'notes' => $this->notes,
            'agent_notes' => $this->agent_notes,
            'cancelled_by' => $this->cancelled_by,
            'cancellation_reason' => $this->cancellation_reason,
            'max_attendees' => $this->max_attendees,
            'confirmed_at' => $this->formatDate($this->confirmed_at),
            'completed_at' => $this->formatDate($this->completed_at),
            'cancelled_at' => $this->formatDate($this->cancelled_at),
            'ends_at' => $this->formatDate($this->ends_at ?? null),
        ];
    }
}
