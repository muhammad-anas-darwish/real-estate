<?php

namespace Modules\ServiceProvider\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Core\SubModules\Location\Http\Resources\CityResource;

class ServiceProviderCoverageAreaResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'city' => CityResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'city_id' => $this->city_id,
        ];
    }
}
