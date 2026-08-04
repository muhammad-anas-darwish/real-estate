<?php

namespace Modules\Core\SubModules\Location\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class CountryResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'cities' => CityResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'phone_code' => $this->resource->phone_code,
            'is_active' => $this->resource->is_active,
            'cities_count' => $this->resource->cities_count ?? 0,
        ];
    }
}
