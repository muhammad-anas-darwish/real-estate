<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class AdResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'ad_group' => AdGroupResource::class,
            'property' => PropertyResource::class,
            'creator' => UserResource::class,
            'media' => AdMediaResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'media_type' => $this->media_type?->value,
            'external_url' => $this->external_url,
            'status' => $this->status?->value,
            'is_default' => $this->is_default,
            'start_date' => $this->formatDate($this->start_date),
            'end_date' => $this->formatDate($this->end_date),
            'is_linked_to_property' => $this->property_id !== null,
            'view_count' => $this->when($this->view_count !== null, $this->view_count),
            'visit_count' => $this->when($this->visit_count !== null, $this->visit_count),
            'ctr' => $this->when($this->ctr !== null, $this->ctr),
        ];
    }
}
