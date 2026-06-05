<?php

namespace Modules\RealEstate\Expert\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class ExpertRequestResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'expert_type' => $this->expert_type?->value,
            'expert_type_label' => $this->expert_type?->label(),
            'message' => $this->message,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'user' => UserResource::class,
            'relationship' => ExpertRelationshipResource::class,
        ];
    }
}
