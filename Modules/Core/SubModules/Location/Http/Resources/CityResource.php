<?php

namespace Modules\Core\SubModules\Location\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class CityResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'country' => CountryResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->resource->name,
            'country_id' => $this->resource->country_code,
            'state_provianc' => $this->resource->state_provianc,
            'postal_code' => $this->resource->postal_code,
            'is_active' => $this->resource->is_active,
        ];
    }
}
