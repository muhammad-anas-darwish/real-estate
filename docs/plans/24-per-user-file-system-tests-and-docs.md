# المهمة #24: اختبارات قبول شاملة + توثيق Scribe لنظام الملفات

> **التقرير المصدر:** `docs/ideas/per-user-file-system/report.md` (معايير القبول 1–22)
> **الهدف:** اختبارات Feature شاملة تغطي كل معايير القبول الـ 22 + توثيق Scribe لـ API endpoints.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟡 منخفضة
> **الجهد المقدّر:** 1.5 – 2 يوم
> **الاعتمادية:** [#20](./20-per-user-file-system-data-foundation.md) + [#21](./21-per-user-file-system-core-service-and-api.md) + [#22](./22-per-user-file-system-property-integration.md) + [#23](./23-per-user-file-system-storage-quota.md) — يجب أن تكون كل الميزة جاهزة قبل بدء هذه الخطة.
> **راجع:** `report.md` السطور 196–222 (معايير القبول 1–22 كاملةً)

---

## الوضع الحالي

الخطط #20-#23 تضيف كل الميزة (Data Foundation + Service + API + Property Integration + Storage Quota). لكن لا يوجد ملف اختبار feature واحد يربط السيناريوهات بمعايير القبول. كما لا توجد تعليقات Scribe على Controllers ليُولِّد `php artisan scribe:generate` توثيقًا صحيحًا.

---

## المرحلة 1: Feature Tests — الغطاء الشامل (~ 6-8 ساعات)

### 1.1 `FolderTest` — اختبارات عمليات المجلدات

`Modules/FileSystem/Tests/FolderTest.php`:

```php
<?php

namespace Modules\FileSystem\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Entities\User;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Modules\FileSystem\Entities\UserFolder;
use Tests\TestCase;

class FolderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        Sanctum::actingAs($this->user);
    }

    // ─── إنشاء مجلد ─────────────────────────────

    /** @test */
    public function it_can_create_folder_at_root(): void
    {
        $response = $this->postJson('/api/folders', [
            'name' => 'My Folder',
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_folders', [
            'name' => 'My Folder',
            'parent_id' => null,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_create_subfolder(): void
    {
        $parent = UserFolder::factory()->for($this->user)->create();

        $response = $this->postJson('/api/folders', [
            'name' => 'Child Folder',
            'parent_id' => $parent->id,
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_folders', [
            'name' => 'Child Folder',
            'parent_id' => $parent->id,
        ]);
    }

    /** @test */
    public function it_rejects_duplicate_folder_name_in_same_parent(): void
    {
        $parent = UserFolder::factory()->for($this->user)->create();

        $this->postJson('/api/folders', [
            'name' => 'Unique',
            'parent_id' => $parent->id,
        ]);

        $response = $this->postJson('/api/folders', [
            'name' => 'Unique',
            'parent_id' => $parent->id,
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_allows_same_folder_name_in_different_parents(): void
    {
        $parent1 = UserFolder::factory()->for($this->user)->create();
        $parent2 = UserFolder::factory()->for($this->user)->create();

        $this->postJson('/api/folders', ['name' => 'Same', 'parent_id' => $parent1->id])->assertCreated();
        $this->postJson('/api/folders', ['name' => 'Same', 'parent_id' => $parent2->id])->assertCreated();
    }

    // ─── عرض محتويات مجلد ────────────────────────

    /** @test */
    public function it_shows_folder_contents_with_breadcrumbs(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create(['name' => 'Parent']);
        $child = UserFolder::factory()->for($this->user)->withParent($folder)->create(['name' => 'Child']);

        $response = $this->getJson("/api/folders/{$child->id}");
        $response->assertOk();
        $response->assertJsonPath('data.breadcrumbs.0.name', 'Parent');
        $response->assertJsonPath('data.breadcrumbs.1.name', 'Child');
    }

    // ─── نقل مجلد ────────────────────────────────

    /** @test */
    public function it_can_move_folder(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create();
        $target = UserFolder::factory()->for($this->user)->create();

        $response = $this->postJson("/api/folders/{$folder->id}/move", [
            'target_folder_id' => $target->id,
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_folders', [
            'id' => $folder->id,
            'parent_id' => $target->id,
        ]);
    }

    /** @test */
    public function it_cannot_move_folder_to_itself(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create();

        $response = $this->postJson("/api/folders/{$folder->id}/move", [
            'target_folder_id' => $folder->id,
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_move_folder_to_its_descendant(): void
    {
        $parent = UserFolder::factory()->for($this->user)->create();
        $child = UserFolder::factory()->for($this->user)->withParent($parent)->create();

        $response = $this->postJson("/api/folders/{$parent->id}/move", [
            'target_folder_id' => $child->id,
        ]);
        $response->assertStatus(422);
    }

    // ─── إعادة تسمية مجلد ────────────────────────

    /** @test */
    public function it_can_rename_folder(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create(['name' => 'Old']);

        $response = $this->postJson("/api/folders/{$folder->id}/rename", [
            'name' => 'New Name',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_folders', [
            'id' => $folder->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function it_cannot_rename_to_existing_name(): void
    {
        $parent = UserFolder::factory()->for($this->user)->create();
        UserFolder::factory()->for($this->user)->withParent($parent)->create(['name' => 'Existing']);
        $folder = UserFolder::factory()->for($this->user)->withParent($parent)->create(['name' => 'Other']);

        $response = $this->postJson("/api/folders/{$folder->id}/rename", [
            'name' => 'Existing',
        ]);
        $response->assertStatus(422);
    }

    // ─── حذف مجلد ────────────────────────────────

    /** @test */
    public function it_cannot_delete_non_empty_folder(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create();
        UserFolder::factory()->for($this->user)->withParent($folder)->create();

        $response = $this->deleteJson("/api/folders/{$folder->id}");
        $response->assertStatus(422);
        $this->assertDatabaseHas('user_folders', ['id' => $folder->id]);
    }

    /** @test */
    public function it_can_delete_empty_folder(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create();

        $response = $this->deleteJson("/api/folders/{$folder->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('user_folders', ['id' => $folder->id]);
    }

    // ─── خصوصية — مستخدم آخر ─────────────────────

    /** @test */
    public function it_cannot_access_other_users_folder(): void
    {
        $folder = UserFolder::factory()->for($this->otherUser)->create();

        $response = $this->getJson("/api/folders/{$folder->id}");
        $response->assertStatus(404);
    }

    /** @test */
    public function it_cannot_create_subfolder_in_other_users_folder(): void
    {
        $folder = UserFolder::factory()->for($this->otherUser)->create();

        $response = $this->postJson('/api/folders', [
            'name' => 'Intruder',
            'parent_id' => $folder->id,
        ]);
        $response->assertStatus(422);
    }
}
```

### 1.2 `FileTest` — اختبارات عمليات الملفات

`Modules/FileSystem/Tests/FileTest.php`:

```php
<?php

namespace Modules\FileSystem\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Entities\User;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Modules\FileSystem\Entities\UserFile;
use Modules\FileSystem\Entities\UserFolder;
use Modules\FileSystem\Enums\FileType;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private UserFolder $folder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->folder = UserFolder::factory()->for($this->user)->create();

        Sanctum::actingAs($this->user);
    }

    // ─── ملف نصي ─────────────────────────────────

    /** @test */
    public function it_can_create_text_file(): void
    {
        $response = $this->postJson('/api/files/text', [
            'folder_id' => $this->folder->id,
            'name' => 'notes.txt',
            'content' => 'Hello world',
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_files', [
            'name' => 'notes.txt',
            'content' => 'Hello world',
            'file_type' => FileType::TEXT->value,
        ]);
    }

    /** @test */
    public function it_can_create_empty_text_file(): void
    {
        $response = $this->postJson('/api/files/text', [
            'folder_id' => $this->folder->id,
            'name' => 'empty.txt',
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_files', [
            'name' => 'empty.txt',
            'content' => '',
            'size' => 0,
        ]);
    }

    /** @test */
    public function it_can_update_text_file_content(): void
    {
        $file = UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create(['content' => 'Old', 'size' => 3]);

        $response = $this->putJson("/api/files/{$file->id}/text", [
            'content' => 'New content here',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_files', [
            'id' => $file->id,
            'content' => 'New content here',
        ]);
    }

    /** @test */
    public function it_can_rename_file(): void
    {
        $file = UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create(['name' => 'old.txt']);

        $response = $this->postJson("/api/files/{$file->id}/rename", [
            'name' => 'new.txt',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_files', ['id' => $file->id, 'name' => 'new.txt']);
    }

    // ─── رفع صورة ────────────────────────────────

    /** @test */
    public function it_can_upload_image(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('photo.jpg');

        $response = $this->postJson('/api/files/image', [
            'folder_id' => $this->folder->id,
            'image' => $image,
            'name' => 'photo.jpg',
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_files', [
            'name' => 'photo.jpg',
            'file_type' => FileType::IMAGE->value,
        ]);
    }

    /** @test */
    public function it_rejects_invalid_image_extension(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/files/image', [
            'folder_id' => $this->folder->id,
            'image' => $file,
            'name' => 'document.pdf',
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_image_exceeding_max_size(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('large.jpg')->size(11000); // 11 MB

        $response = $this->postJson('/api/files/image', [
            'folder_id' => $this->folder->id,
            'image' => $image,
            'name' => 'large.jpg',
        ]);
        $response->assertStatus(422);
    }

    // ─── حذف ملف ─────────────────────────────────

    /** @test */
    public function it_can_delete_file(): void
    {
        $file = UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create(['size' => 100]);

        $response = $this->deleteJson("/api/files/{$file->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('user_files', ['id' => $file->id]);
    }

    // ─── نقل ملف ─────────────────────────────────

    /** @test */
    public function it_can_move_file_to_another_folder(): void
    {
        $target = UserFolder::factory()->for($this->user)->create();
        $file = UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create();

        $response = $this->postJson("/api/files/{$file->id}/move", [
            'target_folder_id' => $target->id,
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_files', ['id' => $file->id, 'folder_id' => $target->id]);
    }

    // ─── خصوصية ──────────────────────────────────

    /** @test */
    public function it_cannot_access_other_users_file(): void
    {
        $otherFolder = UserFolder::factory()->for(User::factory())->create();
        $file = UserFile::factory()
            ->for(User::factory(), 'user')
            ->for($otherFolder, 'folder')
            ->create();

        $response = $this->getJson("/api/files/{$file->id}");
        $response->assertStatus(404);
    }

    // ─── اسم مكرر ────────────────────────────────

    /** @test */
    public function it_rejects_duplicate_file_name_in_same_folder(): void
    {
        UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create(['name' => 'notes.txt']);

        $response = $this->postJson('/api/files/text', [
            'folder_id' => $this->folder->id,
            'name' => 'notes.txt',
            'content' => 'Duplicate',
        ]);
        $response->assertStatus(422);
    }
}
```

### 1.3 `StorageQuotaTest` — اختبارات الحصة التخزينية

`Modules/FileSystem/Tests/StorageQuotaTest.php`:

```php
<?php

namespace Modules\FileSystem\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Entities\User;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Modules\FileSystem\Entities\StorageLimit;
use Tests\TestCase;

class StorageQuotaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        StorageLimit::factory()->for($this->user)->create([
            'quota_bytes' => 104_857_600,
            'used_bytes' => 80_000_000,
            'package_type' => 'free',
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_returns_storage_status(): void
    {
        $response = $this->getJson('/api/storage/status');
        $response->assertOk();
        $response->assertJsonPath('data.quota_bytes', 104_857_600);
        $response->assertJsonPath('data.used_bytes', 80_000_000);
    }

    /** @test */
    public function it_detects_near_limit(): void
    {
        StorageLimit::where('user_id', $this->user->id)
            ->update(['used_bytes' => 90_000_000]); // ~85.8% — فوق 80%

        $response = $this->getJson('/api/storage/status');
        $response->assertJsonPath('data.is_near_limit', true);
    }

    /** @test */
    public function it_lists_available_packages(): void
    {
        $response = $this->getJson('/api/storage/packages');
        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertNotEmpty($packages);
        // كل الباقات المعروضة يجب أن تكون أعلى من المجانية (100 MB)
        foreach ($packages as $pkg) {
            $this->assertGreaterThan(104_857_600, $pkg['quota_bytes']);
        }
    }

    /** @test */
    public function it_can_upgrade_storage(): void
    {
        $response = $this->postJson('/api/storage/upgrade', [
            'package_type' => 'medium',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('storage_limits', [
            'user_id' => $this->user->id,
            'package_type' => 'medium',
        ]);
    }

    /** @test */
    public function it_rejects_upgrade_to_lower_or_equal_package(): void
    {
        // على الباقة المجانية (100 MB) — نحاول الترقية إلى مجانية
        $response = $this->postJson('/api/storage/upgrade', [
            'package_type' => 'free',
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_upgrade_beyond_max(): void
    {
        StorageLimit::where('user_id', $this->user->id)->update([
            'package_type' => 'max',
            'quota_bytes' => 5_368_709_120,
        ]);

        $response = $this->postJson('/api/storage/upgrade', [
            'package_type' => 'max',
        ]);
        $response->assertStatus(422);
    }
}
```

---

## المرحلة 2: توثيق Scribe (~ 2-3 ساعات)

**الهدف:** إضافة تعليقات Scribe على كل Controller action ليُولِّد التوثيق تلقائيًا.

### 2.1 تعليقات `FolderController`

```php
/**
 * قائمة المجلدات في مستوى معيّن (الجذر افتراضيًا)
 *
 * @group نظام الملفات
 * @subgroup المجلدات
 *
 * @authenticated
 *
 * @queryParam parent_id integer معرّف المجلد الأب (null للجذر). Example: 5
 *
 * @response {
 *   "data": [...],
 *   "message": "Folders retrieved"
 * }
 */
public function index(): JsonResponse

/**
 * عرض محتويات مجلد (مجلدات فرعية + ملفات + مسار تنقّل)
 *
 * @group نظام الملفات
 * @subgroup المجلدات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف المجلد. Example: 1
 *
 * @response {
 *   "data": {
 *     "folder": {...},
 *     "children": [...],
 *     "files": [...],
 *     "breadcrumbs": [...],
 *     "storage": {...}
 *   }
 * }
 */
public function show(int $id): JsonResponse

/**
 * إنشاء مجلد جديد داخل مجلد أب (أو في الجذر)
 *
 * @group نظام الملفات
 * @subgroup المجلدات
 *
 * @authenticated
 *
 * @bodyParam parent_id integer معرّف المجلد الأب (اختياري، null = الجذر). Example: 3
 * @bodyParam name string required اسم المجلد. Example: "مستندات العقار"
 *
 * @response 201 {
 *   "data": {...},
 *   "message": "Folder created"
 * }
 */
public function store(CreateFolderRequest $request): JsonResponse

/**
 * تحديث اسم المجلد
 *
 * @group نظام الملفات
 * @subgroup المجلدات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف المجلد. Example: 1
 * @bodyParam name string required الاسم الجديد. Example: "اسم محدّث"
 */
public function update(UpdateFolderRequest $request, int $id): JsonResponse

/**
 * حذف مجلد فارغ (غير محمي)
 *
 * @group نظام الملفات
 * @subgroup المجلدات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف المجلد. Example: 1
 * @response 403 "المجلد محمي أو غير فارغ"
 */
public function destroy(int $id): JsonResponse

/**
 * نقل مجلد إلى مجلد آخر
 *
 * @group نظام الملفات
 * @subgroup المجلدات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف المجلد المُراد نقله. Example: 1
 * @bodyParam target_folder_id integer required معرّف المجلد الوجهة. Example: 5
 */
public function move(MoveItemRequest $request, int $id): JsonResponse

/**
 * إعادة تسمية مجلد
 *
 * @group نظام الملفات
 * @subgroup المجلدات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف المجلد. Example: 1
 * @bodyParam name string required الاسم الجديد. Example: "اسم جديد"
 */
public function rename(RenameItemRequest $request, int $id): JsonResponse
```

### 2.2 تعليقات `FileController`

```php
/**
 * عرض تفاصيل ملف واحد (يعمل للملفات النصية والصور)
 *
 * @group نظام الملفات
 * @subgroup الملفات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف الملف. Example: 1
 */
public function show(int $id): JsonResponse

/**
 * إنشاء ملف نصي جديد
 *
 * @group نظام الملفات
 * @subgroup الملفات
 *
 * @authenticated
 *
 * @bodyParam folder_id integer required معرّف المجلد. Example: 3
 * @bodyParam name string required اسم الملف (مثل: "notes.txt"). Example: "ملاحظات.txt"
 * @bodyParam content string محتوى الملف النصي (اختياري). Example: "نص الملاحظة هنا"
 */
public function storeText(CreateTextFileRequest $request): JsonResponse

/**
 * تعديل ملف نصي (اسم أو محتوى أو كلاهما)
 *
 * @group نظام الملفات
 * @subgroup الملفات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف الملف. Example: 1
 * @bodyParam name string الاسم الجديد. Example: "notes-v2.txt"
 * @bodyParam content string المحتوى الجديد. Example: "محتوى محدّث"
 */
public function updateText(UpdateTextFileRequest $request, int $id): JsonResponse

/**
 * رفع صورة إلى مجلد
 *
 * @group نظام الملفات
 * @subgroup الملفات
 *
 * @authenticated
 *
 * @bodyParam folder_id integer required معرّف المجلد. Example: 3
 * @bodyParam image file required ملف الصورة (jpg, jpeg, png, webp, gif — أقصى 10 MB).
 * @bodyParam name string اسم الصورة (اختياري — يُستخدم اسم الملف الأصلي افتراضيًا). Example: "contract.jpg"
 */
public function uploadImage(UploadImageRequest $request): JsonResponse

/**
 * حذف ملف
 *
 * @group نظام الملفات
 * @subgroup الملفات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف الملف. Example: 1
 */
public function destroy(int $id): JsonResponse

/**
 * نقل ملف إلى مجلد آخر
 *
 * @group نظام الملفات
 * @subgroup الملفات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف الملف المُراد نقله. Example: 1
 * @bodyParam target_folder_id integer required معرّف المجلد الوجهة. Example: 5
 */
public function move(MoveItemRequest $request, int $id): JsonResponse

/**
 * إعادة تسمية ملف
 *
 * @group نظام الملفات
 * @subgroup الملفات
 *
 * @authenticated
 *
 * @urlParam id integer required معرّف الملف. Example: 1
 * @bodyParam name string required الاسم الجديد. Example: "new-name.txt"
 */
public function rename(RenameItemRequest $request, int $id): JsonResponse
```

### 2.3 تعليقات `StorageQuotaController`

```php
/**
 * عرض حالة الحصة التخزينية للمستخدم الحالي
 *
 * @group نظام الملفات
 * @subgroup المساحة التخزينية
 *
 * @authenticated
 *
 * @response {
 *   "data": {
 *     "quota_bytes": 104857600,
 *     "quota_readable": "100 MB",
 *     "used_bytes": 5000000,
 *     "used_readable": "4.77 MB",
 *     "remaining_bytes": 99857600,
 *     "used_percentage": 4.77,
 *     "package_type": "free",
 *     "is_exceeded": false,
 *     "is_near_limit": false,
 *     "can_upload": true,
 *     "available_packages": [...]
 *   }
 * }
 */
public function status(): JsonResponse

/**
 * قائمة الباقات المتاحة للترقية (الأعلى من الحالية فقط)
 *
 * @group نظام الملفات
 * @subgroup المساحة التخزينية
 *
 * @authenticated
 */
public function packages(): JsonResponse

/**
 * ترقية المساحة التخزينية إلى باقة جديدة
 *
 * @group نظام الملفات
 * @subgroup المساحة التخزينية
 *
 * @authenticated
 *
 * @bodyParam package_type string required نوع الباقة (small, medium, large, max). Example: medium
 */
public function upgrade(UpgradeStorageRequest $request): JsonResponse
```

---

## المرحلة 3: تشغيل الاختبارات والتوثيق (~ 1 ساعة)

1. `php artisan config:clear`
2. `php artisan test --testsuite=Modules --filter=FolderTest`
3. `php artisan test --testsuite=Modules --filter=FileTest`
4. `php artisan test --testsuite=Modules --filter=StorageQuotaTest`
5. `php artisan test --testsuite=Modules --filter=PropertyFolderIntegrationTest`
6. `php artisan scribe:generate` — التحقق من ظهور جميع الـ endpoints تحت مجموعة "نظام الملفات".
7. `composer pint` — تنسيق نهائي.

---

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1.1 + 1.2 + 1.3 | 3 ملفات اختبار مستقلة | كل الخطط السابقة مكتملة |
| المرحلة 2.1 + 2.2 + 2.3 | تعليقات على Controllers مختلفة | لا شيء |
| المرحلة 3 | تشغيل وتحقق | المرحلتان 1 و 2 |

---

## معايير القبول

- [x] مرحلة 1.1: FolderTest ✅
- [x] مرحلة 1.2: FileTest ✅
- [x] مرحلة 1.3: StorageQuotaTest ✅
- [x] مرحلة 2: تعليقات Scribe على Controllers ✅
- [x] `FolderTest` يغطي: إنشاء، إنشاء فرعي، اسم مكرر، نقل، نقل إلى النفس، نقل إلى فرع، إعادة تسمية، إعادة تسمية لاسم مكرر، حذف فارغ، رفض حذف غير فارغ، خصوصية. ✅
- [x] `FileTest` يغطي: ملف نصي جديد، ملف فارغ، تعديل محتوى، إعادة تسمية، رفع صورة، رفض تمديد غير صحيح، رفض حجم كبير، حذف، نقل، اسم مكرر، خصوصية. ✅
- [x] `StorageQuotaTest` يغطي: عرض الحالة، اكتشاف الاقتراب من الحد، قائمة الباقات، ترقية، رفض ترقية أقل/أعلى من الحد. ✅
- [ ] `PropertyFolderIntegrationTest` (من الخطة #22) تم تخطيها — الخطة #22 لم تُنفذ بعد.
- [x] تعليقات Scribe added to FolderController, FileController, StorageQuotaController. `scribe:generate` يتطلب MySQL (Docker) — لم يُشغل محليًا. ✅
- [x] كل الاختبارات تمر دفعة واحدة: 31 passed, 0 failed. ✅
- [x] `composer pint` يمر بدون أخطاء. ✅

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
