<?php

namespace Modules\RealEstate\Expert\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Communication\Http\Resources\ChatRoomResource;

class ExpertRelationshipResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'expert' => UserResource::class,
            'user' => UserResource::class,
            'room' => ChatRoomResource::class,
        ];
    }
}
