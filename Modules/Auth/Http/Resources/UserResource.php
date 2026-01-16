<?php

namespace Modules\Auth\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class UserResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            // Add relations here if needed
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
