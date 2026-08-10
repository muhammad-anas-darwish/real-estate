<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class RentalCardCollection extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'tenant_name' => $this->tenant_display_name,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status?->value,
            'is_renewable' => (bool) $this->is_renewable,
            'days_remaining' => $this->days_remaining,
        ];
    }
}
