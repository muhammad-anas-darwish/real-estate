<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;

readonly final class PropertyDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?int $country_id = null,
        public ?int $city_id = null,
        public ?float $longitude = null,
        public ?float $latitude = null,
        public ?int $rooms = null,
        public ?int $bathrooms = null,
        public ?float $area = null,
        public ?string $detailed_info = null,
        public ?string $type_of_contract = null,
        public ?string $property_type = null,
        public ?float $price = null,
        public ?string $currency = null,
        public ?int $publisher_id = null,
        public ?int $approved_by = null,
        public ?string $status = null,
        public ?array $main_image = null,
        public ?array $gallery = null,
    ) {
    }

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            description: $array['description'] ?? null,
            country_id: $array['country_id'] ?? null,
            city_id: $array['city_id'] ?? null,
            longitude: $array['longitude'] ?? null,
            latitude: $array['latitude'] ?? null,
            type_of_contract: $array['type_of_contract'] ?? null,
            property_type: $array['property_type'] ?? null,
            rooms: $array['rooms'] ?? null,
            bathrooms: $array['bathrooms'] ?? null,
            area: $array['area'] ?? null,
            detailed_info: $array['detailed_info'] ?? null,
            price: $array['price'] ?? null,
            currency: $array['currency'] ?? null,
            publisher_id: $array['publisher_id'] ?? null,
            approved_by: $array['approved_by'] ?? null,
            status: $array['status'] ?? null,
            main_image: $array['main_image'] ?? null,
            gallery: $array['gallery'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'type_of_contract' => $this->type_of_contract,
            'property_type' => $this->property_type,
            'rooms' => $this->rooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,
            'detailed_info' => $this->detailed_info,
            'price' => $this->price,
            'currency' => $this->currency,
            'publisher_id' => $this->publisher_id,
            'approved_by' => $this->approved_by,
            'status' => $this->status,
        ], fn($value) => $value !== null);
    }
}
