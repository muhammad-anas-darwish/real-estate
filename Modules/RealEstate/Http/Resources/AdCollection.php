<?php

namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AdCollection extends ResourceCollection
{
    public $collects = AdResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
