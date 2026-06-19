<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Core\SubModules\Location\Http\Resources\CityResource;
use Modules\Core\SubModules\Location\Http\Resources\CountryResource;
use Modules\Core\TemporaryFile\Http\Resources\TemporaryFileResource;

class PropertyResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'publisher' => UserResource::class,
            'approver' => UserResource::class,
            'media' => TemporaryFileResource::class,
            'city' => CityResource::class,
            'country' => CountryResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            // Basic Information
            'name' => $this->name,
            'description' => $this->description,

            // Location
            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'country' => $this->whenLoaded('country', CountryResource::class),
            'city' => $this->whenLoaded('city', CityResource::class),

            'type_of_contract' => $this->type_of_contract,
            'property_type' => $this->property_type,

            // Property Details
            'rooms' => $this->rooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,

            // Detailed Information
            'detailed_info' => $this->detailed_info,

            // Price
            'price' => $this->price,
            'currency' => $this->currency,
            'formatted_price' => $this->currency.' '.number_format($this->price, 2),

            // Status
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'is_loved' => (bool) ($this->is_loved ?? false),
            'views' => $this->views,

            // Images
            'main_image' => $this->main_image_url,
            'main_image_thumb' => $this->main_image_thumb_url,
            'gallery' => $this->gallery_urls,

            // Foreign Keys
            'publisher_id' => $this->publisher_id,
            'publisher_type' => $this->publisher_type,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->formatDate($this->approved_at),

            // Verified Badge
            'publisher_is_verified' => $this->publisher ? $this->publisher->hasVerifiedBadge() : false,
        ];
    }
}
