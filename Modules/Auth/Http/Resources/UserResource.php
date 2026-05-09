<?php

namespace Modules\Auth\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class UserResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'roles' => RoleResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
        ];
    }
}
