<?php

namespace Modules\Communication\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class ChatRoomResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'participants' => UserResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'type' => $this->type->value,
            'name' => $this->name,
            'property_id' => $this->property_id,
        ];
    }
}
