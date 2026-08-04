<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class AdDisplayResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'media_type' => $this->media_type?->value,
            'external_url' => $this->external_url,
            'media_urls' => $this->relationLoaded('media')
                ? $this->media->map(fn ($m) => AdMediaResource::make($m))->values()->toArray()
                : [],
            'has_link' => ! empty($this->external_url),
        ];
    }
}
