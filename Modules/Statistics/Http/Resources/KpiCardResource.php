<?php

namespace Modules\Statistics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KpiCardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'key' => $this->resource['key'] ?? null,
            'label' => $this->resource['label'] ?? null,
            'value' => $this->resource['value'] ?? 0,
            'previous_value' => $this->resource['previous_value'] ?? null,
            'change_percent' => $this->resource['change_percent'] ?? null,
            'change_direction' => $this->resource['change_direction'] ?? null,
            'format' => $this->resource['format'] ?? 'number',
            'icon' => $this->resource['icon'] ?? null,
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn ($item) => (new self($item))->toArray(null), $items);
    }
}
