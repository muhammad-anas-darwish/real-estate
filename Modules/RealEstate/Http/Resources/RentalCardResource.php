<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class RentalCardResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'property' => PropertyResource::class,
            'owner' => UserResource::class,
            'tenantUser' => UserResource::class,
            'endedBy' => UserResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'property_id' => $this->property_id,
            'owner_id' => $this->owner_id,
            'tenant' => [
                'type' => $this->is_external_tenant ? 'external' : 'registered',
                'user_id' => $this->tenant_user_id,
                'name' => $this->tenant_display_name,
                'phone' => $this->tenant_user_id ? null : $this->external_tenant_phone,
                'email' => $this->tenant_user_id ? null : $this->external_tenant_email,
                'id_notes' => $this->tenant_user_id ? null : $this->external_tenant_id_notes,
            ],
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'days_remaining' => $this->days_remaining,
            'terms' => $this->terms,
            'notes' => $this->notes,
            'is_renewable' => (bool) $this->is_renewable,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'is_active' => $this->status?->value === 'active',
            'renewal_count' => (int) $this->renewal_count,
            'ended_at' => $this->ended_at?->format('Y-m-d H:i:s'),
            'end_reason' => $this->end_reason,
            'pre_rental_photos' => $this->getMedia('pre_rental')->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'file_name' => $m->file_name,
                'mime_type' => $m->mime_type,
                'size' => $m->size,
                'url' => $m->getUrl(),
                'thumb_url' => $m->getUrl('thumb'),
                'medium_url' => $m->getUrl('medium'),
            ])->values(),
            'pre_rental_photos_count' => $this->getMedia('pre_rental')->count(),
        ];
    }
}
