<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\DTOs\MapFilterDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Http\Resources\MapPropertyResource;

class MapController extends Controller
{
    use ApiResponses;

    public function properties(Request $request): JsonResponse
    {
        $filter = MapFilterDTO::fromRequest($request->all());

        $query = Property::query()
            ->where('status', PropertyStatus::APPROVED)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($filter->propertyType) {
            $query->where('property_type', $filter->propertyType);
        }
        if ($filter->contractType) {
            $query->where('type_of_contract', $filter->contractType);
        }
        if ($filter->priceMin !== null) {
            $query->where('price', '>=', $filter->priceMin);
        }
        if ($filter->priceMax !== null) {
            $query->where('price', '<=', $filter->priceMax);
        }
        if ($filter->roomsMin !== null) {
            $query->where('rooms', '>=', $filter->roomsMin);
        }
        if ($filter->areaMin !== null) {
            $query->where('area', '>=', $filter->areaMin);
        }
        if ($filter->cityId) {
            $query->where('city_id', $filter->cityId);
        }
        if ($filter->countryId) {
            $query->where('country_id', $filter->countryId);
        }
        if ($filter->publisherType !== null) {
            $query->where('publisher_type', $filter->publisherType);
        }
        if ($filter->createdWithinDays) {
            $query->where('created_at', '>=', now()->subDays($filter->createdWithinDays));
        }
        if ($filter->bounds) {
            $query->whereBetween('latitude', [$filter->bounds->swLat, $filter->bounds->neLat])
                ->whereBetween('longitude', [$filter->bounds->swLng, $filter->bounds->neLng]);
        }

        $totalMatching = (clone $query)->count();
        $properties = $query->with('city:id,name')
            ->limit($filter->limit)
            ->get();

        $isTruncated = $totalMatching > $filter->limit;

        return $this->successResponse([
            'properties' => MapPropertyResource::collection($properties),
            'meta' => [
                'total_matching' => $totalMatching,
                'returned' => $properties->count(),
                'is_truncated' => $isTruncated,
                'limit' => $filter->limit,
                'truncation_message' => $isTruncated
                    ? "يتم عرض {$filter->limit} من {$totalMatching} عقار. كبّر الخريطة أو طبّق فلاتر أضيق."
                    : null,
            ],
            'filter' => $filter->toArray(),
        ]);
    }

    public function traderCompetitiveMap(Request $request): JsonResponse
    {
        $traderId = auth()->id();
        $cityId = $request->input('city_id');
        $propertyType = $request->input('property_type');

        $myProperties = Property::where('publisher_id', $traderId)
            ->where('status', PropertyStatus::APPROVED)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->when($propertyType, fn ($q) => $q->where('property_type', $propertyType))
            ->get();

        $competitorProperties = Property::where('publisher_id', '!=', $traderId)
            ->where('status', PropertyStatus::APPROVED)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->when($propertyType, fn ($q) => $q->where('property_type', $propertyType))
            ->limit(500)
            ->get();

        return $this->successResponse([
            'my_properties' => MapPropertyResource::collection($myProperties),
            'competitor_properties' => MapPropertyResource::collection($competitorProperties),
            'meta' => [
                'my_count' => $myProperties->count(),
                'competitor_count' => $competitorProperties->count(),
                'is_competitor_truncated' => $competitorProperties->count() >= 500,
            ],
        ]);
    }
}
