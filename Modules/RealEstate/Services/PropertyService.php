<?php

namespace Modules\RealEstate\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Modules\Core\TemporaryFile\Services\MediaSyncService;
use Modules\RealEstate\DTOs\PropertyDTO;
use Modules\RealEstate\Entities\Property;

class PropertyService
{
    public function __construct(
        protected MediaSyncService $mediaSyncService
    ) {}

    public function all(): LengthAwarePaginator
    {
        return Property::query()
            ->with(['city', 'country', 'publisher', 'approver', 'media' => fn($query) => $query->where('collection_name', 'main_image')])
            ->orderBy(request('sort_by', 'created_at'), request('sort_order', 'desc'))
            ->paginate(request('perPage', 15));
    }

    public function find($id): Property
    {
        return Property::with(['city', 'country', 'publisher', 'approver', 'media'])->findOrFail($id);
    }

    public function store(PropertyDTO $dto): Property
    {
        return DB::transaction(function () use ($dto): Property {
            $property = Property::create(array_merge($dto->toArray(), ['publisher_id' => \Auth::id()]));

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

            return $property->fresh(['publisher', 'approver', 'media' => fn($query) => $query->where('collection_name', 'main_image')]);
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

            return $property->fresh(['publisher', 'approver', 'media' => fn($query) => $query->where('collection_name', 'main_image')]);
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

    public function approve(int $id, int $approverId): Property
    {
        return DB::transaction(function () use ($id, $approverId): Property {
            $property = Property::findOrFail($id);
            $property->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);
            return $property->fresh(['publisher', 'approver', 'media' => fn($query) => $query->where('collection_name', 'main_image')]);
        });
    }

    public function reject(int $id, int $approverId): Property
    {
        return DB::transaction(function () use ($id, $approverId): Property {
            $property = Property::findOrFail($id);
            $property->update([
                'status' => 'rejected',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);
            return $property->fresh(['publisher', 'approver', 'media' => fn($query) => $query->where('collection_name', 'main_image')]);
        });
    }

    public function markAsSold(int $id): Property
    {
        return DB::transaction(function () use ($id): Property {
            $property = Property::findOrFail($id);

            $user = auth()->user();
            if (!$user->can('properties.mark_as_sold') && $property->publisher_id !== $user->id) {
                throw new UnauthorizedException(__('exceptions.mark_as_sold_unauthorized'), 403);
            }

            $property->update(['status' => 'sold']);
            return $property->fresh(['publisher', 'approver', 'media' => fn($query) => $query->where('collection_name', 'main_image')]);
        });
    }
}
