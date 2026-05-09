<?php

namespace Modules\Auth\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\DTOs\UserDTO;
use Modules\Auth\Entities\User;

class UserManagementService extends BaseService
{
    public const CACHE_TAG = 'users';

    private const CACHE_TTL = 86400;

    public function all(): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey(request()->query(), 'list');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () {
            $query = User::query()
                ->with('roles')
                ->filter()
                ->orderBy(request('sort_by', 'created_at'), request('sort_order', 'desc'));

            if (request()->has('role_id')) {
                $query->whereHas('roles', fn ($q) => $q->where('id', request('role_id')));
            }

            return $query->paginate($this->getPerPage());
        });
    }

    public function find(int $id): User
    {
        $cacheKey = $this->generateCacheKey(['id' => $id], 'item');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return User::with('roles')->findOrFail($id);
        });
    }

    public function store(UserDTO $dto): User
    {
        return DB::transaction(function () use ($dto): User {
            $data = array_filter([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password ? Hash::make($dto->password) : null,
                'status' => $dto->status ?? 'active',
            ], fn ($value) => $value !== null);

            $user = User::create($data);

            if (! empty($dto->role_ids)) {
                $user->syncRoles($dto->role_ids);
            }

            $this->clearCache();

            return $user->load('roles');
        });
    }

    public function update(int $id, UserDTO $dto): User
    {
        return DB::transaction(function () use ($id, $dto): User {
            $user = User::findOrFail($id);

            $data = array_filter([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password ? Hash::make($dto->password) : null,
                'status' => $dto->status,
            ], fn ($value) => $value !== null);

            $user->update($data);

            if (! empty($dto->role_ids)) {
                $user->syncRoles($dto->role_ids);
            }

            $this->clearCache();

            return $user->fresh()->load('roles');
        });
    }

    public function destroy(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $user = User::findOrFail($id);
            $user->delete();
            $this->clearCache();
        });
    }

    public function toggleStatus(int $id): User
    {
        return DB::transaction(function () use ($id): User {
            $user = User::findOrFail($id);
            $user->status = $user->status === 'active' ? 'inactive' : 'active';
            $user->save();
            $this->clearCache();

            return $user->fresh()->load('roles');
        });
    }
}
