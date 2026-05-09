<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\TemporaryFile\Services\MediaSyncService;
use Modules\RealEstate\DTOs\PropertyDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;

class PropertyService extends BaseService
{
    public function __construct(
        protected MediaSyncService $mediaSyncService
    ) {}

    public function all(?int $userId = null): LengthAwarePaginator
    {
        return Property::query()
            ->filter()
            ->priceRange(request('price_min'), request('price_max'))
            ->areaRange(request('area_min'), request('area_max'))
            ->roomsRange(request('rooms_min'), request('rooms_max'))
            ->bathroomsRange(request('bathrooms_min'), request('bathrooms_max'))
            ->with(['city', 'country', 'publisher', 'approver', 'media' => fn ($query) => $query->where('collection_name', 'main_image')])
            ->withExists(['favoritedBy as is_loved' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->orderBy($this->resolveSortBy(), request('sort_order', 'desc'))
            ->paginate($this->getPerPage());
    }

    public function publicProperties(?int $userId = null): LengthAwarePaginator
    {
        return Property::query()
            ->whereIn('status', [PropertyStatus::APPROVED, PropertyStatus::SOLD])
            ->filter()
            ->priceRange(request('price_min'), request('price_max'))
            ->areaRange(request('area_min'), request('area_max'))
            ->roomsRange(request('rooms_min'), request('rooms_max'))
            ->bathroomsRange(request('bathrooms_min'), request('bathrooms_max'))
            ->with(['city', 'country', 'publisher', 'approver', 'media' => fn ($query) => $query->where('collection_name', 'main_image')])
            ->withExists(['favoritedBy as is_loved' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->orderBy($this->resolveSortBy(), request('sort_order', 'desc'))
            ->paginate($this->getPerPage());
    }

    public function dashboardProperties(?int $userId = null): LengthAwarePaginator
    {
        return Property::query()
            ->filter()
            ->priceRange(request('price_min'), request('price_max'))
            ->areaRange(request('area_min'), request('area_max'))
            ->roomsRange(request('rooms_min'), request('rooms_max'))
            ->bathroomsRange(request('bathrooms_min'), request('bathrooms_max'))
            ->with(['city', 'country', 'publisher', 'approver', 'media' => fn ($query) => $query->where('collection_name', 'main_image')])
            ->withExists(['favoritedBy as is_loved' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->orderBy($this->resolveSortBy(), request('sort_order', 'desc'))
            ->paginate(request('perPage', 15));
    }

    public function find(int $id, ?int $userId = null): Property
    {
        return Property::with(['city', 'country', 'publisher', 'approver', 'media'])
            ->withExists(['favoritedBy as is_loved' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->findOrFail($id);
    }

    public function findPublic(int $id, ?int $userId = null): Property
    {
        $property = Property::whereIn('status', [PropertyStatus::APPROVED, PropertyStatus::SOLD])
            ->with(['city', 'country', 'publisher', 'approver', 'media'])
            ->withExists(['favoritedBy as is_loved' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->findOrFail($id);

        $property->incrementViews();

        return $property;
    }

    public function findDashboard(int $id, ?int $userId = null): Property
    {
        $property = Property::with(['city', 'country', 'publisher', 'approver', 'media'])
            ->withExists(['favoritedBy as is_loved' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->findOrFail($id);

        $property->incrementViews();

        return $property;
    }

    public function store(PropertyDTO $dto): Property
    {
        return DB::transaction(function () use ($dto): Property {
            $property = Property::create(array_merge($dto->toArray(), ['publisher_id' => Auth::id()]));

            // Handle main image
            if ($dto->main_image) {
                $this->mediaSyncService->syncFiles(
                    model: $property,
                    files: [$dto->main_image],
                    ruleName: 'property_images',
                    collectionName: 'main_image'
                );
            }

            // Handle gallery images
            if ($dto->gallery) {
                $this->mediaSyncService->syncFiles(
                    model: $property,
                    files: $dto->gallery,
                    ruleName: 'property_images',
                    collectionName: 'gallery'
                );
            }

            return $property->fresh(['publisher', 'approver', 'media' => fn ($query) => $query->where('collection_name', 'main_image')]);
        });
    }

    public function update(int $id, PropertyDTO $dto): Property
    {
        return DB::transaction(function () use ($id, $dto): Property {
            $property = Property::findOrFail($id);
            $property->update($dto->toArray());

            // Handle main image
            if ($dto->main_image !== null) {
                $this->mediaSyncService->syncFiles(
                    model: $property,
                    files: [$dto->main_image],
                    ruleName: 'property_images',
                    collectionName: 'main_image'
                );
            }

            // Handle gallery images
            if ($dto->gallery !== null) {
                $this->mediaSyncService->syncFiles(
                    model: $property,
                    files: $dto->gallery,
                    ruleName: 'property_images',
                    collectionName: 'gallery'
                );
            }

            return $property->fresh(['publisher', 'approver', 'media' => fn ($query) => $query->where('collection_name', 'main_image')]);
        });
    }

    public function destroy(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $property = Property::findOrFail($id);
            $property->clearMediaCollection('main_image');
            $property->clearMediaCollection('gallery');
            $property->delete();
        });
    }

    public function myProperties(int $userId): LengthAwarePaginator
    {
        return Property::query()
            ->byPublisher($userId)
            ->filter()
            ->priceRange(request('price_min'), request('price_max'))
            ->areaRange(request('area_min'), request('area_max'))
            ->roomsRange(request('rooms_min'), request('rooms_max'))
            ->bathroomsRange(request('bathrooms_min'), request('bathrooms_max'))
            ->with(['city', 'country', 'publisher', 'approver', 'media' => fn ($query) => $query->where('collection_name', 'main_image')])
            ->withExists(['favoritedBy as is_loved' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->orderBy($this->resolveSortBy(), request('sort_order', 'desc'))
            ->paginate(request('perPage', 15));
    }

    public function random(int $count = 10, ?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        return Property::query()
            ->whereIn('status', [PropertyStatus::APPROVED, PropertyStatus::SOLD])
            ->with(['city', 'country', 'publisher', 'approver', 'media' => fn ($query) => $query->where('collection_name', 'main_image')])
            ->withExists(['favoritedBy as is_loved' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->inRandomOrder()
            ->limit($count)
            ->get();
    }

    public function updateStatus(int $id, string $status): Property
    {
        $property = Property::findOrFail($id);

        $statusEnum = PropertyStatus::tryFrom($status);
        if (! $statusEnum) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $updateData = ['status' => $statusEnum->value];

        if ($statusEnum === PropertyStatus::APPROVED) {
            $updateData['approved_by'] = Auth::id();
            $updateData['approved_at'] = now();
        }

        $property->update($updateData);

        return $property->fresh(['publisher', 'approver', 'media' => fn ($query) => $query->where('collection_name', 'main_image')]);
    }

    public function statistics(): array
    {
        $statistics = Property::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = PropertyStatus::values();
        $result = [];
        $total = 0;

        foreach ($statuses as $status) {
            $count = $statistics[$status] ?? 0;
            $result[$status] = $count;
            $total += $count;
        }

        $result['all'] = $total;

        return $result;
    }

    private function resolveSortBy(): string
    {
        $allowed = ['price', 'area', 'rooms', 'bathrooms', 'created_at', 'views'];
        $sortBy = request('sort_by', 'created_at');

        return in_array($sortBy, $allowed) ? $sortBy : 'created_at';
    }
}
