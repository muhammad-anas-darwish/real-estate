<?php

namespace Modules\Statistics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TopListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'title' => $this->resource['title'] ?? null,
            'items' => $this->resource['items'] ?? [],
            'sort_by' => $this->resource['sort_by'] ?? null,
        ];
    }
}
