<?php

namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MapPropertyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'property_type' => $this->property_type?->value,
            'type_of_contract' => $this->type_of_contract?->value,
            'rooms' => $this->rooms,
            'bathrooms' => $this->bathrooms,
            'area' => (float) $this->area,
            'city_id' => $this->city_id,
            'city_name' => $this->city?->name,
            'main_image' => $this->main_image_url,
            'publisher_id' => $this->publisher_id,
            'is_physically_verified' => (bool) $this->is_physically_verified,
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
