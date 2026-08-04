<?php

namespace Modules\Core\Category\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class CategoryResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            // Add relations here
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}
