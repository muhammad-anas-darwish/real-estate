<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Support\Facades\URL;
use Modules\RealEstate\Enums\AdMediaType;

class AdMediaResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'file_url' => $this->file_path
                ? URL::temporarySignedRoute('ad-media.show', now()->addMinutes(60), ['media' => $this->id])
                : null,
            'thumbnail_url' => $this->file_path && $this->media_type === AdMediaType::IMAGE
                ? URL::temporarySignedRoute('ad-media.thumbnail', now()->addMinutes(60), ['media' => $this->id])
                : null,
            'media_type' => $this->media_type?->value,
            'sort_order' => $this->sort_order,
        ];
    }
}
