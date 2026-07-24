<?php

namespace Modules\Ai\DTOs;

use App\Interfaces\DTOInterface;

final readonly class ExtractedRequirements implements DTOInterface
{
    public function __construct(
        public ?string $propertyType = null,
        public ?int $roomsMin = null,
        public ?int $areaMin = null,
        public ?string $city = null,
        public ?float $priceMin = null,
        public ?float $priceMax = null,
        public array $features = [],
        public string $language = 'ar',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            propertyType: $data['property_type'] ?? null,
            roomsMin: isset($data['rooms_min']) ? (int) $data['rooms_min'] : null,
            areaMin: isset($data['area_min']) ? (int) $data['area_min'] : null,
            city: $data['city'] ?? null,
            priceMin: isset($data['price_min']) ? (float) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (float) $data['price_max'] : null,
            features: $data['features'] ?? [],
            language: $data['language'] ?? 'ar',
        );
    }

    public function toArray(): array
    {
        return [
            'property_type' => $this->propertyType,
            'rooms_min' => $this->roomsMin,
            'area_min' => $this->areaMin,
            'city' => $this->city,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
            'features' => $this->features,
            'language' => $this->language,
        ];
    }
}
