<?php

namespace Modules\ServiceProvider\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class ServiceRequestTaskResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [];
    }

    protected function getCustomData(): array
    {
        return [
            'service_request_id' => $this->service_request_id,
            'task_type' => $this->task_type,
            'checklist_json' => $this->checklist_json,
            'completed_at' => $this->formatDate($this->completed_at),
        ];
    }
}
