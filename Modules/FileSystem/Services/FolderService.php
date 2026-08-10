<?php

namespace Modules\FileSystem\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\FileSystem\DTOs\CreateFolderDTO;
use Modules\FileSystem\DTOs\MoveItemDTO;
use Modules\FileSystem\DTOs\RenameItemDTO;
use Modules\FileSystem\DTOs\UpdateFolderDTO;
use Modules\FileSystem\Entities\UserFolder;

class FolderService extends BaseService
{
    protected const CACHE_TAG = 'user_folders';

    public function listRoot(int $userId, ?int $parentId = null): LengthAwarePaginator
    {
        $query = UserFolder::query()
            ->filter()
            ->forUser($userId)
            ->withCount(['children', 'files']);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->root();
        }

        return $query
            ->orderBy('is_protected', 'desc')
            ->orderBy('name')
            ->paginate($this->getPerPage(50));
    }

    public function find(int $id): UserFolder
    {
        return UserFolder::withCount(['children', 'files'])
            ->findOrFail($id);
    }

    public function contents(int $folderId, int $userId): array
    {
        $folder = $this->find($folderId);
        $this->assertOwnership($folder, $userId);

        $folders = UserFolder::forUser($userId)
            ->where('parent_id', $folderId)
            ->orderBy('name')
            ->get();

        $files = $folder->files()
            ->orderBy('name')
            ->get();

        $breadcrumbs = $this->buildBreadcrumbs($folder);

        return [
            'folder' => $folder,
            'children' => $folders,
            'files' => $files,
            'breadcrumbs' => $breadcrumbs,
        ];
    }

    public function create(CreateFolderDTO $dto): UserFolder
    {
        return DB::transaction(function () use ($dto) {
            $this->assertParentValid($dto->user_id, $dto->parent_id);
            $this->assertUniqueName($dto->user_id, $dto->parent_id, $dto->name);

            $folder = UserFolder::create(array_merge($dto->toArray(), [
                'folder_type' => 'regular',
            ]));

            $this->clearCache();

            return $folder;
        });
    }

    public function update(int $id, UpdateFolderDTO $dto, int $userId): UserFolder
    {
        return DB::transaction(function () use ($id, $dto, $userId) {
            $folder = $this->find($id);
            $this->assertOwnership($folder, $userId);
            $this->assertNotProtected($folder);
            $this->assertUniqueName($userId, $folder->parent_id, $dto->name, $folder->id);

            $folder->update($dto->toArray());
            $this->clearCache();

            return $folder->fresh();
        });
    }

    public function delete(int $id, int $userId): void
    {
        DB::transaction(function () use ($id, $userId) {
            $folder = $this->find($id);
            $this->assertOwnership($folder, $userId);
            $this->assertNotProtected($folder);
            $this->assertEmptyFolder($folder);

            $folder->delete();
            $this->clearCache();
        });
    }

    public function move(int $id, MoveItemDTO $dto, int $userId): UserFolder
    {
        return DB::transaction(function () use ($id, $dto, $userId) {
            $folder = $this->find($id);
            $this->assertOwnership($folder, $userId);
            $this->assertNotProtected($folder);

            $targetFolder = $this->find($dto->target_folder_id);
            $this->assertOwnership($targetFolder, $userId);

            if ($folder->id === $targetFolder->id) {
                throw new \InvalidArgumentException(__('messages.cannot_move_to_self'));
            }
            if ($targetFolder->isDescendantOf($folder)) {
                throw new \InvalidArgumentException(__('messages.cannot_move_to_descendant'));
            }

            $this->assertUniqueName($userId, $dto->target_folder_id, $folder->name);

            $folder->update(['parent_id' => $dto->target_folder_id]);
            $this->clearCache();

            return $folder->fresh();
        });
    }

    public function rename(int $id, RenameItemDTO $dto, int $userId): UserFolder
    {
        return DB::transaction(function () use ($id, $dto, $userId) {
            $folder = $this->find($id);
            $this->assertOwnership($folder, $userId);
            $this->assertNotProtected($folder);
            $this->assertUniqueName($userId, $folder->parent_id, $dto->name, $folder->id);

            $folder->update(['name' => $dto->name]);
            $this->clearCache();

            return $folder->fresh();
        });
    }

    private function assertOwnership(UserFolder $folder, int $userId): void
    {
        if ($folder->user_id !== $userId) {
            abort(403, __('messages.unauthorized_access'));
        }
    }

    private function assertNotProtected(UserFolder $folder): void
    {
        if ($folder->is_protected) {
            $message = $folder->isPropertyFolder()
                ? __('messages.property_folder_is_protected')
                : __('messages.folder_is_protected');
            abort(403, $message);
        }
    }

    private function assertEmptyFolder(UserFolder $folder): void
    {
        if ($folder->children()->exists() || $folder->files()->exists()) {
            throw new \InvalidArgumentException(__('messages.folder_not_empty'));
        }
    }

    private function assertParentValid(int $userId, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }
        $parent = UserFolder::findOrFail($parentId);
        if ($parent->user_id !== $userId) {
            abort(403, __('messages.unauthorized_access'));
        }
    }

    private function assertUniqueName(int $userId, ?int $parentId, string $name, ?int $excludeId = null): void
    {
        $query = UserFolder::forUser($userId)
            ->where('parent_id', $parentId)
            ->where('name', $name);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($query->exists()) {
            throw new \InvalidArgumentException(__('messages.duplicate_name_in_folder'));
        }
    }

    private function buildBreadcrumbs(UserFolder $folder): array
    {
        $crumbs = [];
        $current = $folder;
        while ($current) {
            array_unshift($crumbs, [
                'id' => $current->id,
                'name' => $current->name,
                'is_protected' => $current->is_protected,
            ]);
            $current = $current->parent;
        }

        return $crumbs;
    }
}
