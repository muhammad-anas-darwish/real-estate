<?php

namespace Modules\Crm\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\RealEstate\Http\Resources\AppointmentResource;

class LeadResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'source' => $this->source?->value,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'lost_reason' => $this->lost_reason,
            'status_changed_at' => $this->formatDate($this->status_changed_at),
            'archived_at' => $this->formatDate($this->archived_at),
            'last_activity_at' => $this->formatDate($this->last_activity_at),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'notes' => LeadNoteResource::class,
            'followUps' => AppointmentResource::class,
            'trader' => UserResource::class,
        ];
    }
}
