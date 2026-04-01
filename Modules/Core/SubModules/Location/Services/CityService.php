<?php

namespace Modules\Core\SubModules\Location\Services;

use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\DTOs\CityDTO;

class CityService extends BaseService
{
    public const CACHE_TAG = 'cities';
    private const CACHE_TTL = 86400; // 1 day

    public function all(): LengthAwarePaginator
    {
        if (!config('services.location.city_cache_enabled', false)) {
            return City::query()->filter()->paginate($this->getPerPage());
        }

        $cacheKey = $this->generateCacheKey(request()->query(), 'list');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () {
            return City::query()->filter()->paginate($this->getPerPage());
        });
    }

    public function find(int $id): City
    {
        if (!config('services.location.city_cache_enabled', false)) {
            return City::findOrFail($id);
        }

        $cacheKey = $this->generateCacheKey(['id' => $id], 'item');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return City::findOrFail($id);
        });
    }

    public function findWithoutRelations(int $id): City
    {
        if (!config('services.location.city_cache_enabled', false)) {
            return City::findOrFail($id);
        }

        $cacheKey = $this->generateCacheKey(['id' => $id], 'item-without-relations');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, fn() => City::findOrFail($id));
    }

    public function store(CityDTO $dto): City
    {
        return DB::transaction(function () use ($dto): City {
            $city = City::create([
                'name' => $dto->name,
                'country_id' => $dto->country_id,
                'state_provianc' => $dto->state_province,
                'postal_code' => $dto->postal_code,
                'is_active' => $dto->is_active,
            ]);

            $this->clearCache();
            return $city;
        });
    }

    public function update(City $city, CityDTO $dto): City
    {
        return DB::transaction(function () use ($city, $dto): City {
            $city->update([
                'name' => $dto->name,
                'country_id' => $dto->country_id,
                'state_provianc' => $dto->state_province,
                'postal_code' => $dto->postal_code,
                'is_active' => $dto->is_active,
            ]);

            $this->clearCache();
            return $city->fresh();
        });
    }

    public function destroy(City $city): void
    {
        DB::transaction(function () use ($city): void {
            $city->delete();
            $this->clearCache();
        });
    }
}
