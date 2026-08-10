<?php

namespace Modules\Ai\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'properties' => $this->resource['properties'] ?? [],
            'extracted_requirements' => $this->resource['extracted_requirements'] ?? [],
            'meta' => [
                'total_matching' => $this->resource['total_matching'] ?? 0,
                'returned' => $this->resource['returned'] ?? 0,
                'is_truncated' => ($this->resource['total_matching'] ?? 0) > 20,
            ],
        ];
    }
}
