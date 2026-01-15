<?php

namespace Modules\Core\Category\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\Category\DTOs\CategoryDTO;
use Modules\Core\Category\Entities\Category;

class CategoryService extends BaseService
{
    public const CACHE_TAG = 'categories';
    private const CACHE_TTL = 86400; // 1 day

    public function all(): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey(request()->query(), 'list');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () {
            return Category::query()
                ->orderBy(request('sort_by', 'created_at'), request('sort_order', 'desc'))
                ->paginate(request('perPage', 15));
        });
    }

    public function find($id): Category
    {
        $cacheKey = $this->generateCacheKey(['id' => $id], 'item');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return Category::findOrFail($id);
        });
    }

    public function store(CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($dto): Category {
            $category = Category::create($dto->toArray());

            $this->clearCache();
            return $category;
        });
    }

    public function update(int $id, CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($id, $dto): Category {
            $category = Category::findOrFail($id);
            $category->update($dto->toArray());

            $this->clearCache();
            return $category->fresh();
        });
    }

    public function destroy(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $category = Category::findOrFail($id);
            $category->delete();
            $this->clearCache();
        });
    }
}
