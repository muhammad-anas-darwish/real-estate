# المهمة #21: الخدمة الأساسية وسطح API لنظام الملفات (Core Service + API Surface)

> **التقرير المصدر:** `docs/ideas/per-user-file-system/report.md` (السيناريوهات 1–6، القواعد 1–27، الحالات الاستثنائية)
> **الهدف:** بناء `FolderService` + `FileService` (كل عمليات CRUD، نقل، إعادة تسمية، حذف، تحرير نصوص، رفع صور) + Controllers + FormRequests + Resources + Routes + Policies.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🔴 عالية
> **الجهد المقدّر:** 2 – 3 أيام
> **الاعتمادية:** [#20](./20-per-user-file-system-data-foundation.md) — يجب أن تكون Migration + Models + Enums + DTOs جاهزة.
> **راجع:** `report.md` السطور 26–87 (السيناريوهات 1–6)، 110–147 (القواعد 1–27)، 176–194 (الحالات الاستثنائية)، 196–222 (معايير القبول)

---

## الوضع الحالي

بعد اكتمال الخطة #20، نملك الجداول والنماذج وDTOs والصلاحيات، لكن لا توجد:
- خدمة (`Service`) تتعامل مع العمليات الأساسية (إنشاء/تعديل/نقل/حذف/إعادة تسمية مجلد وملف، رفع صور، تحرير نصوص).
- نقاط نهاية REST API للمستخدم لاستدعاء هذه العمليات.
- تحقق من صحة المدخلات (FormRequests).
- تحكم بالصلاحيات (Policies).

هذه الخطة تسدّ هذه الفجوات وتبني سطح API كاملًا.

---

## المرحلة 1: Policies (~ 1 ساعة)

### 1.1 `FolderPolicy`

`Modules/FileSystem/Policies/FolderPolicy.php`:

```php
<?php

namespace Modules\FileSystem\Policies;

use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFolder;

class FolderPolicy
{
    public function before(?User $user, string $ability): ?bool
    {
        if ($user?->hasRole('super-admin')) {
            return true;
        }

        return $user?->can("files.{$ability}");
    }

    public function list(User $user): bool
    {
        return $user->can('files.list');
    }

    public function show(User $user, UserFolder $folder): bool
    {
        return $folder->user_id === $user->id || $user->can('files.show');
    }

    public function create(User $user): bool
    {
        return $user->can('files.create');
    }

    public function edit(User $user, UserFolder $folder): bool
    {
        return $folder->user_id === $user->id;
    }

    public function delete(User $user, UserFolder $folder): bool
    {
        if ($folder->is_protected) {
            return false;
        }
        return $folder->user_id === $user->id;
    }

    public function move(User $user, UserFolder $folder): bool
    {
        return $folder->isMovable() && $folder->user_id === $user->id;
    }

    public function rename(User $user, UserFolder $folder): bool
    {
        return $folder->isMovable() && $folder->user_id === $user->id;
    }
}
```

### 1.2 `FilePolicy`

`Modules/FileSystem/Policies/FilePolicy.php`:

```php
<?php

namespace Modules\FileSystem\Policies;

use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFile;

class FilePolicy
{
    public function before(?User $user, string $ability): ?bool
    {
        if ($user?->hasRole('super-admin')) {
            return true;
        }

        return $user?->can("files.{$ability}");
    }

    public function list(User $user): bool
    {
        return $user->can('files.list');
    }

    public function show(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id || $user->can('files.show');
    }

    public function create(User $user): bool
    {
        return $user->can('files.create');
    }

    public function edit(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function delete(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function move(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function rename(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id;
    }
}
```

> **ملاحظة:** تحديث `FileSystemServiceProvider::$policies` (من الخطة #20) لتعيين الـ policies الكاملة (لم تعد فارغة).

---

## المرحلة 2: `FolderService` (~ 4-5 ساعات)

**الهدف:** خدمة تدير كل عمليات المجلدات — إنشاء، عرض، تحديث، حذف، نقل، إعادة تسمية، سرد المحتويات.

`Modules/FileSystem/Services/FolderService.php`:

```php
<?php

namespace Modules\FileSystem\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
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

        // Breadcrumb: سلسلة الآباء حتى الجذر
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
            $this->assertNotProtected($folder, 'rename');
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
            $this->assertNotProtected($folder, 'delete');
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
            $this->assertNotProtected($folder, 'move');

            $targetFolder = $this->find($dto->target_folder_id);
            $this->assertOwnership($targetFolder, $userId);

            // منع النقل إلى نفسه أو إلى أحد فروعه
            if ($folder->id === $targetFolder->id) {
                throw new \InvalidArgumentException(__('messages.cannot_move_to_self'));
            }
            if ($folder->isDescendantOf($targetFolder)) {
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
            $this->assertNotProtected($folder, 'rename');
            $this->assertUniqueName($userId, $folder->parent_id, $dto->name, $folder->id);

            $folder->update(['name' => $dto->name]);
            $this->clearCache();
            return $folder->fresh();
        });
    }

    // ─── Helper methods ────────────────────────────────────────

    private function assertOwnership(UserFolder $folder, int $userId): void
    {
        if ($folder->user_id !== $userId) {
            abort(403, __('messages.unauthorized_access'));
        }
    }

    private function assertNotProtected(UserFolder $folder, string $operation): void
    {
        if ($folder->is_protected) {
            abort(403, __('messages.folder_is_protected'));
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
```

---

## المرحلة 3: `FileService` (~ 5-6 ساعات)

**الهدف:** خدمة تدير كل عمليات الملفات — إنشاء ملف نصي، تعديل محتوى، رفع صورة، حذف، نقل، إعادة تسمية + تتبع الحصة التخزينية.

`Modules/FileSystem/Services/FileService.php`:

```php
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

            if (!empty($updateData)) {
                $file->update($updateData);
            }

            // تعديل الحصة إذا تغيّر الحجم
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

            $path = $image->store('user-files/' . $userId, self::DISK);
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

    // ─── Helper methods ────────────────────────────────────────

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

        if (!in_array($extension, $allowed, true)) {
            throw new \InvalidArgumentException(__('messages.invalid_image_extension', ['extensions' => implode(', ', $allowed)]));
        }

        if ($image->getSize() > FileType::IMAGE->maxSizeBytes()) {
            throw new \InvalidArgumentException(__('messages.image_too_large', ['max' => FileType::IMAGE->maxSizeBytes()]));
        }

        // التحقق من النوع الفعلي (وليس الامتداد فقط)
        $mimeType = $image->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mimeType, $allowedMimes, true)) {
            throw new \InvalidArgumentException(__('messages.invalid_image_type'));
        }
    }

    private function assertQuotaAvailable(int $userId, int $additionalBytes): void
    {
        $limit = StorageLimit::forUser($userId)->firstOrFail();
        if (!$limit->hasAvailableSpace($additionalBytes)) {
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
```

---

## المرحلة 4: FormRequests (~ 2-3 ساعات)

### 4.1 `CreateFolderRequest`

`Modules/FileSystem/Http/Requests/CreateFolderRequest.php`:

```php
<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FileSystem\Entities\UserFolder;

class CreateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.create');
    }

    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('user_folders', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
```

### 4.2 `UpdateFolderRequest`

`Modules/FileSystem/Http/Requests/UpdateFolderRequest.php`:
- نفس نمط `CreateFolderRequest` — `name` فقط مطلوب.

### 4.3 `CreateTextFileRequest`

`Modules/FileSystem/Http/Requests/CreateTextFileRequest.php`:

```php
<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FileSystem\Entities\UserFolder;

class CreateTextFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.create');
    }

    public function rules(): array
    {
        return [
            'folder_id' => [
                'required', 'integer',
                Rule::exists('user_folders', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5242880'], // 5 MB text limit
        ];
    }
}
```

### 4.4 `UpdateTextFileRequest`

`Modules/FileSystem/Http/Requests/UpdateTextFileRequest.php`:
- `name`: nullable, string, max:255
- `content`: nullable, string, max:5242880

### 4.5 `UploadImageRequest`

`Modules/FileSystem/Http/Requests/UploadImageRequest.php`:

```php
<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FileSystem\Entities\UserFolder;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.create');
    }

    public function rules(): array
    {
        return [
            'folder_id' => [
                'required', 'integer',
                Rule::exists('user_folders', 'id')->where('user_id', $this->user()->id),
            ],
            'image' => ['required', 'file', 'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:10240', // 10 MB
            ],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

### 4.6 `MoveItemRequest`

`Modules/FileSystem/Http/Requests/MoveItemRequest.php`:

```php
<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FileSystem\Entities\UserFolder;

class MoveItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.move');
    }

    public function rules(): array
    {
        return [
            'target_folder_id' => [
                'required', 'integer',
                Rule::exists('user_folders', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
```

### 4.7 `RenameItemRequest`

`Modules/FileSystem/Http/Requests/RenameItemRequest.php`:
- `name`: required, string, max:255

---

## المرحلة 5: Resources (~ 1.5-2 ساعات)

### 5.1 `FolderResource`

`Modules/FileSystem/Http/Resources/FolderResource.php`:

```php
<?php

namespace Modules\FileSystem\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class FolderResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'folder_type' => $this->folder_type,
            'is_protected' => $this->is_protected,
            'parent_id' => $this->parent_id,
            'can_move' => $this->isMovable(),
            'can_delete' => !$this->is_protected,
            'can_rename' => $this->isMovable(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'children' => self::class,
            'parent' => self::class,
            'user' => \Modules\Auth\Http\Resources\UserResource::class,
        ];
    }

    protected function getCounts(): array
    {
        return [
            'children_count' => $this->whenCounted('children'),
            'files_count' => $this->whenCounted('files'),
        ];
    }
}
```

### 5.2 `FileResource`

`Modules/FileSystem/Http/Resources/FileResource.php`:

```php
<?php

namespace Modules\FileSystem\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Support\Facades\Storage;

class FileResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'file_type' => $this->file_type?->value,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_readable' => $this->formatBytes($this->size),
            'content' => $this->when($this->isText(), $this->content),
            'preview_url' => $this->when($this->isImage(), fn () =>
                $this->file_path ? url('storage/' . $this->file_path) : null
            ),
            'download_url' => $this->when($this->isImage(), fn () =>
                $this->file_path ? url('storage/' . $this->file_path) : null
            ),
            'folder_id' => $this->folder_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'folder' => FolderResource::class,
            'user' => \Modules\Auth\Http\Resources\UserResource::class,
        ];
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }
}
```

### 5.3 `StorageLimitResource`

`Modules/FileSystem/Http/Resources/StorageLimitResource.php`:

```php
<?php

namespace Modules\FileSystem\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class StorageLimitResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'user_id' => $this->user_id,
            'quota_bytes' => $this->quota_bytes,
            'used_bytes' => $this->used_bytes,
            'remaining_bytes' => $this->remaining_bytes,
            'used_percentage' => $this->used_percentage,
            'package_type' => $this->package_type?->value,
            'package_expires_at' => $this->package_expires_at?->format('Y-m-d H:i:s'),
            'is_exceeded' => $this->isExceeded(),
            'is_near_limit' => $this->isNearLimit(),
        ];
    }
}
```

---

## المرحلة 6: Controllers + Routes (~ 3-4 ساعات)

### 6.1 `FolderController`

`Modules/FileSystem/Http/Controllers/FolderController.php`:

```php
<?php

namespace Modules\FileSystem\Http\Controllers;

use App\Models\Controller;
use Illuminate\Http\JsonResponse;
use Modules\FileSystem\DTOs\CreateFolderDTO;
use Modules\FileSystem\DTOs\MoveItemDTO;
use Modules\FileSystem\DTOs\RenameItemDTO;
use Modules\FileSystem\DTOs\UpdateFolderDTO;
use Modules\FileSystem\Http\Requests\CreateFolderRequest;
use Modules\FileSystem\Http\Requests\MoveItemRequest;
use Modules\FileSystem\Http\Requests\RenameItemRequest;
use Modules\FileSystem\Http\Requests\UpdateFolderRequest;
use Modules\FileSystem\Services\FolderService;

class FolderController extends Controller
{
    public function __construct(
        private FolderService $service,
    ) {}

    /**
     * قائمة المجلدات في مستوى معيّن (الجذر افتراضيًا)
     */
    public function index(): JsonResponse
    {
        $folders = $this->service->listRoot(
            auth()->id(),
            request('parent_id')
        );
        return $this->paginatedResponse($folders, 'Folders retrieved');
    }

    /**
     * عرض محتويات مجلد (مجلدات فرعية + ملفات + مسار تنقّل)
     */
    public function show(int $id): JsonResponse
    {
        $contents = $this->service->contents($id, auth()->id());
        return $this->successResponse($contents, 'Folder contents retrieved');
    }

    /**
     * إنشاء مجلد جديد
     */
    public function store(CreateFolderRequest $request): JsonResponse
    {
        $dto = CreateFolderDTO::fromRequest($request->validated() + ['user_id' => auth()->id()]);
        $folder = $this->service->create($dto);
        return $this->successResponse($folder, 'Folder created')->created('Folder');
    }

    /**
     * تحديث اسم المجلد
     */
    public function update(UpdateFolderRequest $request, int $id): JsonResponse
    {
        $dto = UpdateFolderDTO::fromRequest($request->validated());
        $folder = $this->service->update($id, $dto, auth()->id());
        return $this->successResponse($folder, 'Folder updated')->updated('Folder');
    }

    /**
     * حذف مجلد فارغ (غير محمي)
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id, auth()->id());
        return $this->successResponse(null, 'Folder deleted')->deleted('Folder');
    }

    /**
     * نقل مجلد إلى مجلد آخر
     */
    public function move(MoveItemRequest $request, int $id): JsonResponse
    {
        $dto = MoveItemDTO::fromRequest($request->validated());
        $folder = $this->service->move($id, $dto, auth()->id());
        return $this->successResponse($folder, 'Folder moved')->updated('Folder');
    }

    /**
     * إعادة تسمية مجلد
     */
    public function rename(RenameItemRequest $request, int $id): JsonResponse
    {
        $dto = RenameItemDTO::fromRequest($request->validated());
        $folder = $this->service->rename($id, $dto, auth()->id());
        return $this->successResponse($folder, 'Folder renamed')->updated('Folder');
    }
}
```

### 6.2 `FileController`

`Modules/FileSystem/Http/Controllers/FileController.php`:

```php
<?php

namespace Modules\FileSystem\Http\Controllers;

use App\Models\Controller;
use Illuminate\Http\JsonResponse;
use Modules\FileSystem\DTOs\CreateTextFileDTO;
use Modules\FileSystem\DTOs\MoveItemDTO;
use Modules\FileSystem\DTOs\RenameItemDTO;
use Modules\FileSystem\DTOs\UpdateTextFileDTO;
use Modules\FileSystem\Http\Requests\CreateTextFileRequest;
use Modules\FileSystem\Http\Requests\MoveItemRequest;
use Modules\FileSystem\Http\Requests\RenameItemRequest;
use Modules\FileSystem\Http\Requests\UpdateTextFileRequest;
use Modules\FileSystem\Http\Requests\UploadImageRequest;
use Modules\FileSystem\Services\FileService;

class FileController extends Controller
{
    public function __construct(
        private FileService $service,
    ) {}

    /**
     * عرض تفاصيل ملف واحد
     */
    public function show(int $id): JsonResponse
    {
        $file = $this->service->find($id);
        abort_if($file->user_id !== auth()->id(), 403, __('messages.unauthorized_access'));
        return $this->successResponse($file, 'File retrieved');
    }

    /**
     * إنشاء ملف نصي جديد
     */
    public function storeText(CreateTextFileRequest $request): JsonResponse
    {
        $dto = CreateTextFileDTO::fromRequest($request->validated() + ['user_id' => auth()->id()]);
        $file = $this->service->createText($dto);
        return $this->successResponse($file, 'Text file created')->created('File');
    }

    /**
     * تعديل ملف نصي (اسم أو محتوى)
     */
    public function updateText(UpdateTextFileRequest $request, int $id): JsonResponse
    {
        $dto = UpdateTextFileDTO::fromRequest($request->validated());
        $file = $this->service->updateText($id, $dto, auth()->id());
        return $this->successResponse($file, 'Text file updated')->updated('File');
    }

    /**
     * رفع صورة
     */
    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        $image = $request->file('image');
        $name = $request->input('name', $image->getClientOriginalName());
        $file = $this->service->uploadImage(
            (int) $request->input('folder_id'),
            $image,
            $name,
            auth()->id()
        );
        return $this->successResponse($file, 'Image uploaded')->created('File');
    }

    /**
     * حذف ملف
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id, auth()->id());
        return $this->successResponse(null, 'File deleted')->deleted('File');
    }

    /**
     * نقل ملف إلى مجلد آخر
     */
    public function move(MoveItemRequest $request, int $id): JsonResponse
    {
        $dto = MoveItemDTO::fromRequest($request->validated());
        $file = $this->service->move($id, $dto, auth()->id());
        return $this->successResponse($file, 'File moved')->updated('File');
    }

    /**
     * إعادة تسمية ملف
     */
    public function rename(RenameItemRequest $request, int $id): JsonResponse
    {
        $dto = RenameItemDTO::fromRequest($request->validated());
        $file = $this->service->rename($id, $dto, auth()->id());
        return $this->successResponse($file, 'File renamed')->updated('File');
    }
}
```

### 6.3 Routes

`Modules/FileSystem/Routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\FileSystem\Http\Controllers\FolderController;
use Modules\FileSystem\Http\Controllers\FileController;

Route::middleware('auth:sanctum')->group(function () {
    // ─── المجلدات ──────────────────────────────────
    Route::get('folders', [FolderController::class, 'index'])->name('folders.index');
    Route::post('folders', [FolderController::class, 'store'])->name('folders.store');
    Route::get('folders/{id}', [FolderController::class, 'show'])->name('folders.show');
    Route::put('folders/{id}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('folders/{id}', [FolderController::class, 'destroy'])->name('folders.destroy');
    Route::post('folders/{id}/move', [FolderController::class, 'move'])->name('folders.move');
    Route::post('folders/{id}/rename', [FolderController::class, 'rename'])->name('folders.rename');

    // ─── الملفات ────────────────────────────────────
    Route::get('files/{id}', [FileController::class, 'show'])->name('files.show');
    Route::post('files/text', [FileController::class, 'storeText'])->name('files.text.store');
    Route::put('files/{id}/text', [FileController::class, 'updateText'])->name('files.text.update');
    Route::post('files/image', [FileController::class, 'uploadImage'])->name('files.image.upload');
    Route::delete('files/{id}', [FileController::class, 'destroy'])->name('files.destroy');
    Route::post('files/{id}/move', [FileController::class, 'move'])->name('files.move');
    Route::post('files/{id}/rename', [FileController::class, 'rename'])->name('files.rename');

    // ─── المساحة التخزينية (التحكم في الخطة #23) ────
});
```

> **ملاحظة:** Routes المساحة التخزينية (`quota`) تُضاف في الخطة #23.

---

## المرحلة 7: رسائل الترجمة (~ 30 دقيقة)

إضافة المفاتيح التالية إلى `lang/en/messages.php`:

```php
'folder_not_empty' => 'This folder is not empty. Please delete or move its contents first.',
'folder_is_protected' => 'This folder is protected and cannot be modified.',
'cannot_move_to_self' => 'Cannot move an item to itself.',
'cannot_move_to_descendant' => 'Cannot move a folder into one of its own subfolders.',
'duplicate_name_in_folder' => 'An item with this name already exists in this folder.',
'file_not_owned' => 'You do not own this file.',
'invalid_file_type' => 'Invalid file type for this operation.',
'invalid_image_extension' => 'Invalid image extension. Allowed: :extensions.',
'invalid_image_type' => 'The file is not a valid image.',
'image_too_large' => 'Image exceeds the maximum size of :max bytes.',
'storage_quota_exceeded' => 'Storage quota exceeded. Please upgrade your plan or free up space.',
```

---

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1 (Policies) + المرحلة 7 (ترجمة) | ملفات مستقلة تمامًا | لا شيء |
| المرحلة 2 (FolderService) + المرحلة 3 (FileService) | خدمتان منفصلتان لمنطقين مختلفين | المرحلة 1 (الـ Policies للتضمين الذهني فقط، لكن الكود مستقل) |
| المرحلة 4 (FormRequests) + المرحلة 5 (Resources) | ملفات مستقلة لا تشير لبعضها | لا شيء |
| المرحلة 6 (Controllers + Routes) | تعتمد على الخدمات | المرحلة 2 + 3 + 4 + 5 |

---

## معايير القبول

- [ ] `FolderService::create()` ينشئ مجلدًا ويُمنع تكرار الاسم.
- [ ] `FolderService::move()` ينقل مجلدًا ويمنع النقل إلى النفس أو إلى فرع.
- [ ] `FolderService::delete()` يرفض حذف مجلد محمي أو غير فارغ.
- [ ] `FolderService::contents()` يُرجع المجلدات الفرعية + الملفات + breadcrumbs.
- [ ] `FileService::createText()` ينشئ ملفًا نصيًا ويُحدّث الحصة التخزينية.
- [ ] `FileService::updateText()` يُعدّل المحتوى والاسم ويُصحّح الحصة.
- [ ] `FileService::uploadImage()` يرفع صورة ويتحقق من النوع الفعلي (وليس الامتداد فقط).
- [ ] `FileService::uploadImage()` يرفض الصورة عند امتلاء الحصة.
- [ ] `FileService::delete()` يحذف الملف من القرص ويُحدّث الحصة.
- [ ] FormRequests تتحقق من أن `user_id` للمجلد الأب يخص المستخدم الحالي.
- [ ] `FolderResource` و `FileResource` يُرجعان البيانات بالتنسيق المطلوب.
- [ ] `StorageLimitResource` يعرض `used_percentage`, `remaining_bytes`, `is_near_limit`.
- [ ] الـ routes مسجّلة تحت `auth:sanctum` وتُطبَّق عليها الـ policies.
- [ ] رسائل الخطأ بالعربية والإنجليزية.
- [ ] `composer pint` يمر بدون أخطاء تنسيق.
- [ ] كل العمليات تُنفَّذ داخل `DB::transaction()`.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
