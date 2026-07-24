<?php

namespace Modules\Statistics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DistributionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'dimension' => $this->resource['dimension'] ?? null,
            'items' => $this->resource['items'] ?? [],
            'total' => $this->resource['total'] ?? 0,
        ];
    }
}
