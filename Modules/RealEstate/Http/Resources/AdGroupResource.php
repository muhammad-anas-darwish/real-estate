<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class AdGroupResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'ads' => AdResource::class,
            'creator' => UserResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'is_archived' => $this->is_archived,
            'ads_count' => $this->when($this->ads_count !== null, $this->ads_count),
            'default_ad_id' => $this->default_ad_id,
        ];
    }
}
