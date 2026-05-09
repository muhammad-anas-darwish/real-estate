<?php

namespace Modules\Auth\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class RoleResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'permissions' => PermissionResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ];
    }
}
