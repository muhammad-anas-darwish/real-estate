<?php

namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

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

            // Publisher
            'publisher' => $this->whenLoaded('publisher', function () {
                return [
                    'id' => $this->publisher->id,
                    'name' => $this->publisher->name,
                    'email' => $this->publisher->email,
                ];
            }),
            'publisher_id' => $this->publisher_id,

            // Approval
            'approved_by' => $this->approved_by,
            'approver' => $this->whenLoaded('approver', function () {
                return $this->approver ? [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                ] : null;
            }),
            'approved_at' => $this->approved_at?->toISOString(),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
