<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\TemporaryFile\Services\MediaSyncService;
use Modules\RealEstate\DTOs\CreateAdDTO;
use Modules\RealEstate\DTOs\UpdateAdDTO;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Enums\AdStatus;

class AdService extends BaseService
{
    const CACHE_TAG = 'ads';

    public function __construct(
        protected MediaSyncService $mediaSyncService
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return Cache::tags(static::CACHE_TAG)->remember(
            $this->generateCacheKey($filters, 'all'),
            3600,
            fn () => Ad::query()
                ->filter()
                ->with(['adGroup', 'creator', 'media', 'property'])
                ->orderBy('created_at', 'desc')
                ->paginate($this->getPerPage())
        );
    }

    public function getByGroup(int $groupId): LengthAwarePaginator
    {
        return Ad::where('ad_group_id', $groupId)
            ->with(['creator', 'media', 'property'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());
    }

    public function getForUser(int $userId): LengthAwarePaginator
    {
        return Ad::where('created_by', $userId)
            ->with(['adGroup', 'media', 'property'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());
    }

    public function create(array $data): Ad
    {
        return DB::transaction(function () use ($data): Ad {
            $dto = new CreateAdDTO(
                title: $data['title'] ?? null,
                description: $data['description'] ?? null,
                mediaType: $data['media_type'] ?? null,
                externalUrl: $data['external_url'] ?? null,
                propertyId: $data['property_id'] ?? null,
                adGroupId: $data['ad_group_id'] ?? null,
                status: $data['status'] ?? 'draft',
                startDate: $data['start_date'] ?? null,
                endDate: $data['end_date'] ?? null,
                media: $data['media'] ?? [],
            );

            $ad = Ad::create(array_merge(
                $dto->toArray(),
                ['created_by' => Auth::id()]
            ));

            if (! empty($dto->media)) {
                $this->mediaSyncService->syncFiles(
                    model: $ad,
                    files: $dto->media,
                    ruleName: 'ad_media',
                    collectionName: 'ad_media'
                );
            }

            $this->clearCache();

            return $ad->fresh(['adGroup', 'creator', 'media', 'property']);
        });
    }

    public function update(int $id, array $data): Ad
    {
        return DB::transaction(function () use ($id, $data): Ad {
            $dto = new UpdateAdDTO(
                title: $data['title'] ?? null,
                description: $data['description'] ?? null,
                mediaType: $data['media_type'] ?? null,
                externalUrl: $data['external_url'] ?? null,
                propertyId: $data['property_id'] ?? null,
                adGroupId: $data['ad_group_id'] ?? null,
                status: $data['status'] ?? null,
                startDate: $data['start_date'] ?? null,
                endDate: $data['end_date'] ?? null,
                media: $data['media'] ?? [],
            );

            $ad = Ad::findOrFail($id);
            $ad->update($dto->toArray());

            if (! empty($dto->media)) {
                $this->mediaSyncService->syncFiles(
                    model: $ad,
                    files: $dto->media,
                    ruleName: 'ad_media',
                    collectionName: 'ad_media'
                );
            }

            $this->clearCache();

            return $ad->fresh(['adGroup', 'creator', 'media', 'property']);
        });
    }

    public function setStatus(int $id, string $status): Ad
    {
        $statusEnum = AdStatus::tryFrom($status);
        if (! $statusEnum) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $ad = Ad::findOrFail($id);
        $ad->update(['status' => $statusEnum]);

        $this->clearCache();

        return $ad->fresh(['adGroup', 'creator', 'media', 'property']);
    }

    public function archive(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $ad = Ad::findOrFail($id);

            if ($ad->is_default) {
                $ad->update(['is_default' => false]);
            }

            $ad->delete();

            $this->clearCache();
        });
    }

    public function restore(int $id): void
    {
        $ad = Ad::withTrashed()->findOrFail($id);
        $ad->restore();

        $this->clearCache();
    }

    public function linkToProperty(int $adId, int $propertyId): Ad
    {
        $ad = Ad::findOrFail($adId);
        $ad->update(['property_id' => $propertyId]);

        $this->clearCache();

        return $ad->fresh(['property', 'adGroup', 'creator', 'media']);
    }

    public function unlinkProperty(int $adId): Ad
    {
        $ad = Ad::findOrFail($adId);
        $ad->update(['property_id' => null]);

        $this->clearCache();

        return $ad->fresh(['adGroup', 'creator', 'media']);
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $ad = Ad::withTrashed()->findOrFail($id);

            if ($ad->is_default) {
                $ad->update(['is_default' => false]);
            }

            $ad->forceDelete();

            $this->clearCache();
        });
    }

    public function getEligibleAds(int $groupId, ?Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::today();
        $dateStr = $date->format('Y-m-d');

        return Ad::where('ad_group_id', $groupId)
            ->where('status', AdStatus::ACTIVE)
            ->where('start_date', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $dateStr);
            })
            ->with(['media', 'property'])
            ->get();
    }
}
