<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;
use Modules\RealEstate\ValueObjects\MapBounds;

final readonly class MapFilterDTO implements DTOInterface
{
    public function __construct(
        public ?PropertyType $propertyType = null,
        public ?TypeOfContract $contractType = null,
        public ?float $priceMin = null,
        public ?float $priceMax = null,
        public ?int $roomsMin = null,
        public ?int $areaMin = null,
        public ?int $cityId = null,
        public ?int $countryId = null,
        public ?int $publisherType = null,
        public ?int $createdWithinDays = null,
        public ?MapBounds $bounds = null,
        public ?float $centerLat = null,
        public ?float $centerLng = null,
        public ?float $radiusKm = null,
        public int $limit = 500,
    ) {}

    public static function fromRequest(array $data): self
    {
        $bounds = null;
        if (isset($data['sw_lat'], $data['sw_lng'], $data['ne_lat'], $data['ne_lng'])) {
            $bounds = new MapBounds(
                swLat: (float) $data['sw_lat'],
                swLng: (float) $data['sw_lng'],
                neLat: (float) $data['ne_lat'],
                neLng: (float) $data['ne_lng'],
            );
        } elseif (isset($data['center_lat'], $data['center_lng'], $data['radius_km'])) {
            $bounds = MapBounds::fromCenter(
                (float) $data['center_lat'],
                (float) $data['center_lng'],
                (float) $data['radius_km'],
            );
        }

        return new self(
            propertyType: isset($data['property_type']) ? PropertyType::from($data['property_type']) : null,
            contractType: isset($data['contract_type']) ? TypeOfContract::from($data['contract_type']) : null,
            priceMin: isset($data['price_min']) ? (float) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (float) $data['price_max'] : null,
            roomsMin: isset($data['rooms_min']) ? (int) $data['rooms_min'] : null,
            areaMin: isset($data['area_min']) ? (int) $data['area_min'] : null,
            cityId: isset($data['city_id']) ? (int) $data['city_id'] : null,
            countryId: isset($data['country_id']) ? (int) $data['country_id'] : null,
            publisherType: isset($data['publisher_type']) ? (int) $data['publisher_type'] : null,
            createdWithinDays: isset($data['created_within_days']) ? (int) $data['created_within_days'] : null,
            bounds: $bounds,
            centerLat: isset($data['center_lat']) ? (float) $data['center_lat'] : null,
            centerLng: isset($data['center_lng']) ? (float) $data['center_lng'] : null,
            radiusKm: isset($data['radius_km']) ? (float) $data['radius_km'] : null,
            limit: min(500, max(1, (int) ($data['limit'] ?? 500))),
        );
    }

    public function toArray(): array
    {
        return [
            'property_type' => $this->propertyType?->value,
            'contract_type' => $this->contractType?->value,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
            'rooms_min' => $this->roomsMin,
            'area_min' => $this->areaMin,
            'city_id' => $this->cityId,
            'country_id' => $this->countryId,
            'publisher_type' => $this->publisherType,
            'created_within_days' => $this->createdWithinDays,
            'bounds' => $this->bounds?->toArray(),
            'center_lat' => $this->centerLat,
            'center_lng' => $this->centerLng,
            'radius_km' => $this->radiusKm,
            'limit' => $this->limit,
        ];
    }
}
