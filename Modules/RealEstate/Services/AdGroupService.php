<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\RealEstate\DTOs\CreateAdGroupDTO;
use Modules\RealEstate\DTOs\UpdateAdGroupDTO;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;

class AdGroupService extends BaseService
{
    const CACHE_TAG = 'ad-groups';

    public function getAll(array $filters): LengthAwarePaginator
    {
        return Cache::tags(static::CACHE_TAG)->remember(
            $this->generateCacheKey($filters, 'all'),
            3600,
            fn () => AdGroup::query()
                ->filter()
                ->with(['creator', 'ads' => fn ($q) => $q->withTrashed()])
                ->orderBy('created_at', 'desc')
                ->paginate($this->getPerPage())
        );
    }

    public function getActive(): Collection
    {
        return Cache::tags(static::CACHE_TAG)->remember(
            $this->generateCacheKey([], 'active'),
            3600,
            fn () => AdGroup::active()
                ->with(['creator', 'ads' => fn ($q) => $q->active()])
                ->orderBy('name')
                ->get()
        );
    }

    public function create(array $data): AdGroup
    {
        $dto = new CreateAdGroupDTO(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? 'active',
        );

        $group = AdGroup::create(array_merge(
            $dto->toArray(),
            ['created_by' => Auth::id()]
        ));

        $this->clearCache();

        return $group->fresh(['creator']);
    }

    public function update(int $id, array $data): AdGroup
    {
        $dto = new UpdateAdGroupDTO(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
            isArchived: isset($data['is_archived']) ? (bool) $data['is_archived'] : null,
        );

        $group = AdGroup::findOrFail($id);
        $group->update($dto->toArray());

        $this->clearCache();

        return $group->fresh(['creator', 'ads' => fn ($q) => $q->withTrashed()]);
    }

    public function archive(int $id): void
    {
        $group = AdGroup::findOrFail($id);
        $group->delete();

        $this->clearCache();
    }

    public function restore(int $id): void
    {
        $group = AdGroup::withTrashed()->findOrFail($id);
        $group->restore();

        $this->clearCache();
    }

    public function setDefaultAd(int $groupId, int $adId): Ad
    {
        $group = AdGroup::findOrFail($groupId);

        Ad::where('ad_group_id', $groupId)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $ad = Ad::where('ad_group_id', $groupId)->findOrFail($adId);
        $ad->update(['is_default' => true]);

        $this->clearCache();

        return $ad->fresh();
    }

    public function removeDefaultAd(int $groupId): void
    {
        Ad::where('ad_group_id', $groupId)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $this->clearCache();
    }

    public function getDefaultAd(int $groupId): ?Ad
    {
        return Cache::tags(static::CACHE_TAG)->remember(
            $this->generateCacheKey(['group_id' => $groupId], 'default_ad'),
            3600,
            fn () => Ad::where('ad_group_id', $groupId)
                ->where('is_default', true)
                ->with(['media', 'property'])
                ->first()
        );
    }
}
