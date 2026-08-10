<?php

namespace Modules\RealEstate\ValueObjects;

final readonly class MapBounds
{
    public function __construct(
        public float $swLat,
        public float $swLng,
        public float $neLat,
        public float $neLng,
    ) {
        if ($swLat > $neLat || $swLng > $neLng) {
            throw new \InvalidArgumentException('Invalid MapBounds: SW must be less than NE');
        }
    }

    public static function fromCenter(float $centerLat, float $centerLng, float $radiusKm): self
    {
        $latDelta = $radiusKm / 111;
        $lngDelta = $radiusKm / (111 * cos(deg2rad($centerLat)));

        return new self(
            swLat: $centerLat - $latDelta,
            swLng: $centerLng - $lngDelta,
            neLat: $centerLat + $latDelta,
            neLng: $centerLng + $lngDelta,
        );
    }

    public function contains(float $lat, float $lng): bool
    {
        return $lat >= $this->swLat && $lat <= $this->neLat
            && $lng >= $this->swLng && $lng <= $this->neLng;
    }

    public function toArray(): array
    {
        return [
            'sw' => ['lat' => $this->swLat, 'lng' => $this->swLng],
            'ne' => ['lat' => $this->neLat, 'lng' => $this->neLng],
        ];
    }
}
