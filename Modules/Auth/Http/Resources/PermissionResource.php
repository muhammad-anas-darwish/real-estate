<?php

namespace Modules\Auth\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class PermissionResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ];
    }
}
