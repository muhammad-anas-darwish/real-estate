<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\ValueObjects\MapBounds;

class MapFilterService extends BaseService
{
    public function countInBounds(MapBounds $bounds, ?int $cityId = null): int
    {
        $query = Property::query()
            ->where('status', PropertyStatus::APPROVED)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$bounds->swLat, $bounds->neLat])
            ->whereBetween('longitude', [$bounds->swLng, $bounds->neLng]);

        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        return $query->count();
    }

    public function suggestBroaderBounds(MapBounds $bounds, int $currentCount, int $limit = 500): ?MapBounds
    {
        if ($currentCount <= $limit) {
            return null;
        }

        $latRange = $bounds->neLat - $bounds->swLat;
        $lngRange = $bounds->neLng - $bounds->swLng;

        $expand = 0.25;

        return new MapBounds(
            swLat: $bounds->swLat - $latRange * $expand,
            swLng: $bounds->swLng - $lngRange * $expand,
            neLat: $bounds->neLat + $latRange * $expand,
            neLng: $bounds->neLng + $lngRange * $expand,
        );
    }
}
