<?php

namespace Modules\Statistics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimeSeriesResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'metric' => $this->resource['metric'] ?? null,
            'unit' => $this->resource['unit'] ?? 'count',
            'points' => $this->resource['points'] ?? [],
        ];
    }
}
