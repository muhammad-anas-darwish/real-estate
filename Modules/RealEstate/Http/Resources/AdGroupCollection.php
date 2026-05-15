<?php

namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AdGroupCollection extends ResourceCollection
{
    public $collects = AdGroupResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
