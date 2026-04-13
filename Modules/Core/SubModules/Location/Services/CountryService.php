<?php

namespace Modules\Core\SubModules\Location\Services;

use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Core\SubModules\Location\DTOs\CountryDTO;

class CountryService extends BaseService
{
    public const CACHE_TAG = 'countries';
    private const CACHE_TTL = 86400; // 1 day

    public function all(): LengthAwarePaginator
    {
        if (!config('services.location.country_cache_enabled', false)) {
            return Country::query()->withCount('cities')->filter()->paginate($this->getPerPage());
        }

        $cacheKey = $this->generateCacheKey(request()->query(), 'list');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () {
            return Country::query()->withCount('cities')->filter()->paginate($this->getPerPage());
        });
    }

    public function find(int $id): Country
    {
        if (!config('services.location.country_cache_enabled', false)) {
            return Country::findOrFail($id);
        }

        $cacheKey = $this->generateCacheKey(['id' => $id], 'item');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return Country::findOrFail($id);
        });
    }

    public function findWithoutRelations(int $id): Country
    {
        if (!config('services.location.country_cache_enabled', false)) {
            return Country::findOrFail($id);
        }

        $cacheKey = $this->generateCacheKey(['id' => $id], 'item-without-relations');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, fn() => Country::findOrFail($id));
    }

    public function store(CountryDTO $dto): Country
    {
        return DB::transaction(function () use ($dto): Country {
            $country = Country::create([
                'name' => $dto->name,
                'code' => $dto->code,
                'phone_code' => $dto->phone_code,
                'is_active' => $dto->is_active ?? true,
            ]);

            $this->clearCache();
            return $country;
        });
    }

    public function update(Country $country, CountryDTO $dto): Country
    {
        return DB::transaction(function () use ($country, $dto): Country {
            $country->update([
                'name' => $dto->name,
                'code' => $dto->code,
                'phone_code' => $dto->phone_code,
                'is_active' => $dto->is_active ?? true,
            ]);

            $this->clearCache();
            return $country->fresh();
        });
    }

    public function destroy(Country $country): void
    {
        DB::transaction(function () use ($country): void {
            $country->delete();
            $this->clearCache();
        });
    }
}
