<?php

namespace Modules\Ledger\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class PayrollResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'service_provider_profile_id' => $this->service_provider_profile_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'base_salary' => (float) $this->base_salary,
            'per_task_rate' => (float) $this->per_task_rate,
            'is_active' => $this->is_active,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'serviceProviderProfile' => \Modules\ServiceProvider\Http\Resources\ServiceProviderResource::class,
            'payments' => PayrollPaymentResource::class,
        ];
    }
}
