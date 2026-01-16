<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class PropertyResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'publisher' => UserResource::class,
            'approver' => UserResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            // Basic Information
            'name' => $this->name,
            'description' => $this->description,

            // Location
            'country' => $this->country,
            'city' => $this->city,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'location' => [
                'country' => $this->country,
                'city' => $this->city,
                'coordinates' => [
                    'longitude' => $this->longitude,
                    'latitude' => $this->latitude,
                ],
            ],

            // Property Details
            'rooms' => $this->rooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,

            // Detailed Information
            'detailed_info' => $this->detailed_info,

            // Price
            'price' => $this->price,
            'currency' => $this->currency,
            'formatted_price' => $this->currency . ' ' . number_format($this->price, 2),

            // Status
            'status' => $this->status,

            // Images
            'main_image' => $this->main_image_url,
            'main_image_thumb' => $this->main_image_thumb_url,
            'gallery' => $this->gallery_urls,

            // Foreign Keys
            'publisher_id' => $this->publisher_id,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->formatDate($this->approved_at),
        ];
    }
}
