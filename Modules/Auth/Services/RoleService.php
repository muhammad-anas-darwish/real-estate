<?php

namespace Modules\Auth\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Auth\DTOs\RoleDTO;
use Modules\Auth\Entities\Role;
use Spatie\Permission\Models\Permission;

class RoleService extends BaseService
{
    public const CACHE_TAG = 'roles';

    private const CACHE_TTL = 86400;

    public function all(): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey(request()->query(), 'list');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () {
            return Role::query()
                ->orderBy(request('sort_by', 'created_at'), request('sort_order', 'desc'))
                ->paginate($this->getPerPage());
        });
    }

    public function find(int $id): Role
    {
        $cacheKey = $this->generateCacheKey(['id' => $id], 'item');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return Role::with('permissions')->findOrFail($id);
        });
    }

    public function store(RoleDTO $dto): Role
    {
        return DB::transaction(function () use ($dto): Role {
            $role = Role::create($dto->toArray());
            if (! empty($dto->permissions)) {
                $role->syncPermissions($dto->permissions);
            }
            $this->clearCache();

            return $role->load('permissions');
        });
    }

    public function update(int $id, RoleDTO $dto): Role
    {
        return DB::transaction(function () use ($id, $dto): Role {
            $role = Role::findOrFail($id);
            $role->update($dto->toArray());
            if (! empty($dto->permissions)) {
                $role->syncPermissions($dto->permissions);
            }
            $this->clearCache();

            return $role->fresh()->load('permissions');
        });
    }

    public function destroy(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $role = Role::findOrFail($id);
            $role->delete();
            $this->clearCache();
        });
    }

    public function assignPermissions(int $id, array $permissions): Role
    {
        return DB::transaction(function () use ($id, $permissions): Role {
            $role = Role::findOrFail($id);
            $role->syncPermissions($permissions);
            $this->clearCache();

            return $role->load('permissions');
        });
    }

    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        return Cache::tags(self::CACHE_TAG)->remember('all_permissions', self::CACHE_TTL, function () {
            return Permission::all();
        });
    }
}
