<?php

namespace Modules\Auth\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class UpgradeRequestResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'user' => UserResource::class,
            'reviewer' => UserResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'user_id' => $this->user_id,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->formatDate($this->reviewed_at),
        ];
    }
}
