<?php

namespace Modules\FileSystem\Services;

use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\FileSystem\DTOs\CreateTextFileDTO;
use Modules\FileSystem\DTOs\MoveItemDTO;
use Modules\FileSystem\DTOs\RenameItemDTO;
use Modules\FileSystem\DTOs\UpdateTextFileDTO;
use Modules\FileSystem\Entities\StorageLimit;
use Modules\FileSystem\Entities\UserFile;
use Modules\FileSystem\Entities\UserFolder;
use Modules\FileSystem\Enums\FileType;

class FileService extends BaseService
{
    protected const CACHE_TAG = 'user_files';

    protected const DISK = 'public';

    public function list(int $folderId, int $userId): LengthAwarePaginator
    {
        return UserFile::query()
            ->filter()
            ->forUser($userId)
            ->inFolder($folderId)
            ->orderBy(request('sort_by', 'name'), request('sort_order', 'asc'))
            ->paginate($this->getPerPage(50));
    }

    public function find(int $id): UserFile
    {
        return UserFile::with('folder')->findOrFail($id);
    }

    public function createText(CreateTextFileDTO $dto): UserFile
    {
        return DB::transaction(function () use ($dto) {
            $this->assertFolderOwnedBy($dto->folder_id, $dto->user_id);
            $this->assertUniqueName($dto->user_id, $dto->folder_id, $dto->name);

            $content = $dto->content;
            $size = mb_strlen($content, '8bit');

            $file = UserFile::create([
                'user_id' => $dto->user_id,
                'folder_id' => $dto->folder_id,
                'name' => $dto->name,
                'file_type' => FileType::TEXT,
                'mime_type' => 'text/plain',
                'size' => $size,
                'content' => $content,
            ]);

            $this->incrementStorageUsed($dto->user_id, $size);
            $this->clearCache();

            return $file;
        });
    }

    public function updateText(int $id, UpdateTextFileDTO $dto, int $userId): UserFile
    {
        return DB::transaction(function () use ($id, $dto, $userId) {
            $file = $this->find($id);
            $this->assertOwnership($file, $userId);
            $this->assertFileType($file, FileType::TEXT);

            $oldSize = $file->size;
            $updateData = [];

            if ($dto->name !== null && $dto->name !== $file->name) {
                $this->assertUniqueName($userId, $file->folder_id, $dto->name, $file->id);
                $updateData['name'] = $dto->name;
            }

            if ($dto->content !== null) {
                $updateData['content'] = $dto->content;
                $newSize = mb_strlen($dto->content, '8bit');
                $updateData['size'] = $newSize;
            }

            if (! empty($updateData)) {
                $file->update($updateData);
            }

            $newSize = $updateData['size'] ?? $oldSize;
            if ($newSize !== $oldSize) {
                $this->adjustStorageUsed($userId, $newSize - $oldSize);
            }

            $this->clearCache();

            return $file->fresh();
        });
    }

    public function uploadImage(int $folderId, UploadedFile $image, string $name, int $userId): UserFile
    {
        return DB::transaction(function () use ($folderId, $image, $name, $userId) {
            $this->assertFolderOwnedBy($folderId, $userId);
            $this->assertImageValid($image);
            $this->assertUniqueName($userId, $folderId, $name);
            $this->assertQuotaAvailable($userId, $image->getSize());

            $path = $image->store('user-files/'.$userId, self::DISK);
            $size = $image->getSize();

            $file = UserFile::create([
                'user_id' => $userId,
                'folder_id' => $folderId,
                'name' => $name,
                'file_type' => FileType::IMAGE,
                'mime_type' => $image->getMimeType(),
                'size' => $size,
                'file_path' => $path,
            ]);

            $this->incrementStorageUsed($userId, $size);
            $this->clearCache();

            return $file;
        });
    }

    public function delete(int $id, int $userId): void
    {
        DB::transaction(function () use ($id, $userId) {
            $file = $this->find($id);
            $this->assertOwnership($file, $userId);

            if ($file->isImage() && $file->file_path) {
                Storage::disk(self::DISK)->delete($file->file_path);
            }

            $this->decrementStorageUsed($userId, $file->size);
            $file->delete();
            $this->clearCache();
        });
    }

    public function move(int $id, MoveItemDTO $dto, int $userId): UserFile
    {
        return DB::transaction(function () use ($id, $dto, $userId) {
            $file = $this->find($id);
            $this->assertOwnership($file, $userId);

            $targetFolder = UserFolder::findOrFail($dto->target_folder_id);
            if ($targetFolder->user_id !== $userId) {
                abort(403, __('messages.unauthorized_access'));
            }

            $this->assertUniqueName($userId, $dto->target_folder_id, $file->name, $file->id);

            $file->update(['folder_id' => $dto->target_folder_id]);
            $this->clearCache();

            return $file->fresh();
        });
    }

    public function rename(int $id, RenameItemDTO $dto, int $userId): UserFile
    {
        return DB::transaction(function () use ($id, $dto, $userId) {
            $file = $this->find($id);
            $this->assertOwnership($file, $userId);
            $this->assertUniqueName($userId, $file->folder_id, $dto->name, $file->id);

            $file->update(['name' => $dto->name]);
            $this->clearCache();

            return $file->fresh();
        });
    }

    private function assertOwnership(UserFile $file, int $userId): void
    {
        if ($file->user_id !== $userId) {
            abort(403, __('messages.unauthorized_access'));
        }
    }

    private function assertFileType(UserFile $file, FileType $type): void
    {
        if ($file->file_type !== $type) {
            throw new \InvalidArgumentException(__('messages.invalid_file_type'));
        }
    }

    private function assertFolderOwnedBy(int $folderId, int $userId): void
    {
        $folder = UserFolder::findOrFail($folderId);
        if ($folder->user_id !== $userId) {
            abort(403, __('messages.unauthorized_access'));
        }
    }

    private function assertUniqueName(int $userId, int $folderId, string $name, ?int $excludeId = null): void
    {
        $query = UserFile::forUser($userId)
            ->inFolder($folderId)
            ->where('name', $name);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($query->exists()) {
            throw new \InvalidArgumentException(__('messages.duplicate_name_in_folder'));
        }
    }

    private function assertImageValid(UploadedFile $image): void
    {
        $allowed = FileType::IMAGE->allowedExtensions();
        $extension = strtolower($image->getClientOriginalExtension());

        if (! in_array($extension, $allowed, true)) {
            throw new \InvalidArgumentException(__('messages.invalid_image_extension', ['extensions' => implode(', ', $allowed)]));
        }

        if ($image->getSize() > FileType::IMAGE->maxSizeBytes()) {
            throw new \InvalidArgumentException(__('messages.image_too_large', ['max' => FileType::IMAGE->maxSizeBytes()]));
        }

        $mimeType = $image->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (! in_array($mimeType, $allowedMimes, true)) {
            throw new \InvalidArgumentException(__('messages.invalid_image_type'));
        }
    }

    private function assertQuotaAvailable(int $userId, int $additionalBytes): void
    {
        $limit = StorageLimit::forUser($userId)->first();
        if ($limit && ! $limit->hasAvailableSpace($additionalBytes)) {
            throw new \InvalidArgumentException(__('messages.storage_quota_exceeded'));
        }
    }

    private function incrementStorageUsed(int $userId, int $bytes): void
    {
        StorageLimit::forUser($userId)->increment('used_bytes', $bytes);
    }

    private function decrementStorageUsed(int $userId, int $bytes): void
    {
        StorageLimit::forUser($userId)->decrement('used_bytes', min($bytes, 0));
    }

    private function adjustStorageUsed(int $userId, int $delta): void
    {
        if ($delta > 0) {
            $this->assertQuotaAvailable($userId, $delta);
            $this->incrementStorageUsed($userId, $delta);
        } elseif ($delta < 0) {
            $this->decrementStorageUsed($userId, abs($delta));
        }
    }
}
