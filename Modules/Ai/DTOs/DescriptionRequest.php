<?php

namespace Modules\Ai\DTOs;

use App\Interfaces\DTOInterface;

final readonly class DescriptionRequest implements DTOInterface
{
    public function __construct(
        public ?string $propertyType = null,
        public ?int $rooms = null,
        public ?int $bathrooms = null,
        public ?int $area = null,
        public ?string $city = null,
        public ?float $price = null,
        public array $features = [],
        public ?string $currentDescription = null,
        public string $language = 'ar',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            propertyType: $data['property_type'] ?? null,
            rooms: isset($data['rooms']) ? (int) $data['rooms'] : null,
            bathrooms: isset($data['bathrooms']) ? (int) $data['bathrooms'] : null,
            area: isset($data['area']) ? (int) $data['area'] : null,
            city: $data['city'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            features: $data['features'] ?? [],
            currentDescription: $data['current_description'] ?? null,
            language: $data['language'] ?? 'ar',
        );
    }

    public function toArray(): array
    {
        return [
            'property_type' => $this->propertyType,
            'rooms' => $this->rooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,
            'city' => $this->city,
            'price' => $this->price,
            'features' => $this->features,
            'current_description' => $this->currentDescription,
            'language' => $this->language,
        ];
    }
}
