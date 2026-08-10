# المهمة #22: دمج مجلدات العقارات مع نظام الملفات (Property Folder Integration)

> **التقرير المصدر:** `docs/ideas/per-user-file-system/report.md` (السيناريو 7 + 9، القواعد 9–12، 25، الحالات الاستثنائية)
> **الهدف:** ربط نظام الملفات بالعقارات — إنشاء مجلد تلقائي لكل عقار جديد، حماية مجلدات العقارات من النقل/الحذف/إعادة التسمية، حذف المجلد مع العقار، إظهار تبويب "الملفات" في صفحة العقار.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 متوسطة
> **الجهد المقدّر:** 1 – 1.5 يوم
> **الاعتمادية:** [#20](./20-per-user-file-system-data-foundation.md) + [#21](./21-per-user-file-system-core-service-and-api.md)
> **راجع:** `report.md` السطور 82–89 (السيناريو 7)، 96–97 (السيناريو 9)، 122–124 (القواعد 9–12)، 196–198 (معايير القبول 1–4)

---

## الوضع الحالي

بعد اكتمال الخطتين #20 و #21، نملك نظام ملفات كامل الوظائف (مجلدات + ملفات + API)، لكن لا يوجد ربط مع العقارات. عندما يُنشئ مستخدم عقارًا جديدًا، لا يُنشأ مجلد ملفات تلقائي له. وعند حذف عقار، لا تُحذف ملفاته. أيضًا لا توجد حماية صارمة لمجلدات العقارات ضمن نظام الملفات.

هذه الخطة تربط النظامين وتضيف الحماية الكاملة لمجلدات العقارات.

---

## المرحلة 1: الإنشاء التلقائي لمجلد العقار (~ 2-3 ساعات)

**الهدف:** عند إنشاء عقار جديد، يُنشأ مجلد ملفات مرتبط به تلقائيًا في جذر مساحة المستخدم.

### 1.1 مستمع (Listener) لحدث إنشاء العقار

`Modules/FileSystem/Listeners/CreatePropertyFolder.php`:

```php
<?php

namespace Modules\FileSystem\Listeners;

use Modules\FileSystem\Entities\UserFolder;
use Modules\RealEstate\Events\PropertyCreated;

class CreatePropertyFolder
{
    public function handle(PropertyCreated $event): void
    {
        $property = $event->property;
        $userId = $property->publisher_id;

        UserFolder::create([
            'user_id' => $userId,
            'parent_id' => null, // الجذر
            'name' => __('messages.property_folder_name', ['id' => $property->id, 'title' => $property->title ?? '']),
            'folder_type' => 'property',
            'source_type' => 'Property',
            'source_id' => $property->id,
            'is_protected' => true,
        ]);
    }
}
```

### 1.2 حدث `PropertyCreated`

إذا لم يكن `PropertyCreated` event موجودًا، يُنشأ:

`Modules/RealEstate/Events/PropertyCreated.php`:

```php
<?php

namespace Modules\RealEstate\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\RealEstate\Entities\Property;

class PropertyCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Property $property,
    ) {}
}
```

### 1.3 تشغيل الحدث من `PropertyService::create()`

في `Modules/RealEstate/Services/PropertyService.php` (دالة `create`)، بعد `$property = Property::create(...)`:

```php
event(new PropertyCreated($property));
```

### 1.4 تسجيل المستمع في `FileSystemServiceProvider::boot()`

```php
use Modules\RealEstate\Events\PropertyCreated;
use Modules\FileSystem\Listeners\CreatePropertyFolder;

// في boot():
Event::listen(PropertyCreated::class, CreatePropertyFolder::class);
```

### 1.5 الترجمة

`lang/en/messages.php`:

```php
'property_folder_name' => 'Property #:id — :title',
```

`lang/ar/messages.php`:

```php
'property_folder_name' => 'العقار #:id — :title',
```

---

## المرحلة 2: حماية مجلدات العقارات في الـ Services (~ 2-3 ساعات)

**الهدف:** تعزيز الحماية في `FolderService` و `FileService` لمنع أي عملية على مجلدات العقارات المحمية.

### 2.1 حماية في `FolderService`

الدوال `delete()` و `move()` و `rename()` بالفعل تستدعي `assertNotProtected()` (خطة #21). هذا يمنع العمليات على مجلدات العقارات.

**تعزيز `assertNotProtected` برسالة أوضح:**

```php
private function assertNotProtected(UserFolder $folder, string $operation): void
{
    if ($folder->is_protected) {
        $message = $folder->isPropertyFolder()
            ? __('messages.property_folder_is_protected')
            : __('messages.folder_is_protected');
        abort(403, $message);
    }
}
```

### 2.2 حماية في `FileController` و `FolderController`

الـ policies المنفذة في الخطة #21 (مثل `FolderPolicy::delete()` و `FolderPolicy::move()`) بالفعل تمنع العمليات على المجلدات المحمية. لا حاجة لتعديل إضافي.

### 2.3 إخفاء أزرار العمليات في الـ API Response

`FolderResource` (خطة #21) يُرجع `can_move`, `can_delete`, `can_rename` بناءً على `isMovable()` — وهذا يضمن أن واجهة المستخدم تعرف أي المجلدات محمية دون الحاجة لمنطق إضافي.

### 2.4 منع النقل عبر استهداف مجلد العقار كوجهة

لا حاجة لمنع ذلك — مجلدات العقارات تظهر في الجذر، والمستخدم يستطيع النقل إلى أي مجلد يملكه (بما فيها مجلدات العقارات) طالما أن العنصر المنقول ليس مجلد عقار. هذا سلوك صحيح — المستخدم يستطيع وضع ملفات ومجلدات فرعية داخل مجلد العقار.

---

## المرحلة 3: الحذف التلقائي عند حذف العقار (~ 2 ساعة)

**الهدف:** عند حذف عقار، يُحذف مجلد ملفاته وكل محتوياته بعد تأكيد صريح.

### 3.1 مستمع (Listener) لحذف العقار

`Modules/FileSystem/Listeners/DeletePropertyFolder.php`:

```php
<?php

namespace Modules\FileSystem\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\FileSystem\Entities\UserFile;
use Modules\FileSystem\Entities\UserFolder;
use Modules\FileSystem\Entities\StorageLimit;
use Modules\RealEstate\Events\PropertyDeleting;

class DeletePropertyFolder
{
    public function handle(PropertyDeleting $event): void
    {
        $property = $event->property;

        $folder = UserFolder::where('source_type', 'Property')
            ->where('source_id', $property->id)
            ->first();

        if (! $folder) {
            return;
        }

        DB::transaction(function () use ($folder) {
            $this->deleteFolderRecursive($folder);
        });
    }

    private function deleteFolderRecursive(UserFolder $folder): void
    {
        // حذف المجلدات الفرعية بشكل متكرر
        foreach ($folder->children as $child) {
            $this->deleteFolderRecursive($child);
        }

        // حذف الملفات داخل المجلد مع تحرير المساحة
        $totalSize = 0;
        foreach ($folder->files as $file) {
            if ($file->isImage() && $file->file_path) {
                Storage::disk('public')->delete($file->file_path);
            }
            $totalSize += $file->size;
            $file->delete();
        }

        // تحرير المساحة من حصة المستخدم
        if ($totalSize > 0) {
            StorageLimit::where('user_id', $folder->user_id)
                ->decrement('used_bytes', $totalSize);
        }

        // حذف المجلد نفسه
        $folder->delete();
    }
}
```

### 3.2 حدث `PropertyDeleting`

`Modules/RealEstate/Events/PropertyDeleting.php`:

```php
<?php

namespace Modules\RealEstate\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\RealEstate\Entities\Property;

class PropertyDeleting
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Property $property,
    ) {}
}
```

### 3.3 تشغيل الحدث من `PropertyService::delete()`

في دالة الحذف، قبل `$property->delete()`:

```php
event(new PropertyDeleting($property));
```

### 3.4 تسجيل المستمع

في `FileSystemServiceProvider::boot()`:

```php
use Modules\RealEstate\Events\PropertyDeleting;
use Modules\FileSystem\Listeners\DeletePropertyFolder;

Event::listen(PropertyDeleting::class, DeletePropertyFolder::class);
```

**تنبيه:** يجب تشغيل الـ listener عند حدث `PropertyDeleting` (قبل الحذف) وليس `PropertyDeleted`، لأن العلاقة `source_type` + `source_id` polymorphic تستند إلى وجود العقار. لو شُغّل بعد الحذف، قد يفشل أو يترك بيانات يتيمة.

---

## المرحلة 4: إنشاء المجلد العام (اختياري) (~ 1 ساعة)

**الهدف:** إنشاء مجلد "عام" محمي لكل مستخدم عند تسجيله، للمستندات غير المرتبطة بعقار معيّن.

### 4.1 مستمع لحدث تسجيل المستخدم

`Modules/FileSystem/Listeners/CreateGeneralFolder.php`:

```php
<?php

namespace Modules\FileSystem\Listeners;

use Modules\Auth\Events\UserRegistered;
use Modules\FileSystem\Entities\UserFolder;

class CreateGeneralFolder
{
    public function handle(UserRegistered $event): void
    {
        UserFolder::create([
            'user_id' => $event->user->id,
            'parent_id' => null,
            'name' => __('messages.general_folder_name'),
            'folder_type' => 'general',
            'is_protected' => true,
        ]);
    }
}
```

> **ملاحظة:** إذا لم يكن `UserRegistered` event موجودًا، يُنشأ في `Modules/Auth/Events/UserRegistered.php` ويُطلَق من `FortifyServiceProvider` عند إنشاء مستخدم جديد. بدلاً من ذلك، يمكن استخدام الـ model event مباشرةً: `User::created()`.

### 4.2 الترجمة

```php
'general_folder_name' => 'General Files',        // en
'general_folder_name' => 'ملفات عامة',           // ar
```

### 4.3 تسجيل المستمع

```php
use Modules\Auth\Events\UserRegistered;
use Modules\FileSystem\Listeners\CreateGeneralFolder;

Event::listen(UserRegistered::class, CreateGeneralFolder::class);
```

---

## المرحلة 5: تبويب الملفات في صفحة تفاصيل العقار (~ 1.5 ساعة)

**الهدف:** إضافة نقطة نهاية API تُرجع محتويات مجلد العقار، لاستخدامها في تبويب "الملفات" بواجهة المستخدم.

### 5.1 نقطة نهاية جديدة في `FolderController`

```php
/**
 * عرض محتويات مجلد العقار عبر property_id
 */
public function propertyFolder(int $propertyId): JsonResponse
{
    $folder = UserFolder::where('source_type', 'Property')
        ->where('source_id', $propertyId)
        ->where('user_id', auth()->id())
        ->firstOrFail();

    return $this->show($folder->id);
}
```

### 5.2 Route

```php
Route::get('properties/{propertyId}/files', [FolderController::class, 'propertyFolder'])
    ->name('properties.files');
```

---

## المرحلة 6: اختبار الوحدة (~ 1.5-2 ساعة)

`Modules/FileSystem/Tests/PropertyFolderIntegrationTest.php`:

```php
<?php

namespace Modules\FileSystem\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFolder;
use Modules\FileSystem\Entities\UserFile;
use Modules\FileSystem\Enums\FileType;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Tests\TestCase;

class PropertyFolderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->property = Property::factory()
            ->for($this->user, 'publisher')
            ->create(['status' => PropertyStatus::APPROVED]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_creates_folder_when_property_is_created(): void
    {
        // محاكاة إنشاء العقار مع تشغيل PropertyCreated event
        $property = Property::factory()
            ->for($this->user, 'publisher')
            ->create(['status' => PropertyStatus::APPROVED]);

        // تشغيل الحدث يدويًا (في الواقع سيعمل عبر الـ listener)
        event(new \Modules\RealEstate\Events\PropertyCreated($property));

        $folder = UserFolder::where('source_type', 'Property')
            ->where('source_id', $property->id)
            ->first();

        $this->assertNotNull($folder);
        $this->assertEquals('property', $folder->folder_type);
        $this->assertTrue($folder->is_protected);
    }

    /** @test */
    public function it_prevents_deleting_property_folder(): void
    {
        $folder = UserFolder::factory()
            ->for($this->user)
            ->property()
            ->create();

        $response = $this->deleteJson("/api/folders/{$folder->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('user_folders', ['id' => $folder->id]);
    }

    /** @test */
    public function it_prevents_moving_property_folder(): void
    {
        $folder = UserFolder::factory()
            ->for($this->user)
            ->property()
            ->create();

        $target = UserFolder::factory()->for($this->user)->create();

        $response = $this->postJson("/api/folders/{$folder->id}/move", [
            'target_folder_id' => $target->id,
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function it_deletes_folder_when_property_is_deleted(): void
    {
        // إنشاء مجلد عقار
        $folder = UserFolder::factory()
            ->for($this->user)
            ->property()
            ->state(['source_type' => 'Property', 'source_id' => $this->property->id])
            ->create();

        // إضافة ملف داخل المجلد
        $file = UserFile::factory()
            ->for($this->user)
            ->for($folder, 'folder')
            ->create(['size' => 5000]);

        // حذف العقار
        event(new \Modules\RealEstate\Events\PropertyDeleting($this->property));
        $this->property->delete();

        $this->assertDatabaseMissing('user_folders', ['id' => $folder->id]);
        $this->assertDatabaseMissing('user_files', ['id' => $file->id]);
    }

    /** @test */
    public function it_cannot_access_other_users_property_folder(): void
    {
        $otherUser = User::factory()->create();
        $folder = UserFolder::factory()
            ->for($otherUser)
            ->property()
            ->create();

        $response = $this->getJson("/api/folders/{$folder->id}");
        $response->assertStatus(404); // أو 403 — المهم ألّا يظهر
    }
}
```

---

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1 + 4 | مستمعان مستقلان تمامًا | وجود الـ Events |
| المرحلة 2 + 5 | تعديلات منفصلة على خدمات ومسارات مختلفة | المرحلة 1 |
| المرحلة 3 | مستمع مستقل | المرحلة 1 (نفس نمط الأكواد) |
| المرحلة 6 | اختبارات | المراحل 1–5 |

---

## معايير القبول

- [x] عند إنشاء عقار جديد، يُنشأ مجلد ملفات له تلقائيًا في جذر مساحة المستخدم. ✅
- [x] مجلد العقار يحمل `folder_type = 'property'` و `is_protected = true`. ✅
- [x] لا يمكن نقل مجلد العقار — `POST /folders/{id}/move` يرجع 403. ✅
- [x] لا يمكن حذف مجلد العقار — `DELETE /folders/{id}` يرجع 403. ✅
- [x] لا يمكن إعادة تسمية مجلد العقار — `PUT /folders/{id}` يرجع 403. ✅
- [x] `FolderResource` يُرجع `can_move: false`, `can_delete: false`, `can_rename: false` لمجلدات العقارات. ✅
- [x] عند حذف عقار، يُحذف مجلد ملفاته وكل محتوياته (مجلدات فرعية + ملفات). ✅
- [x] عند حذف مجلد العقار، تُحرَّر المساحة المستهلكة من حصة المستخدم. ✅
- [x] عند إنشاء مستخدم جديد، يُنشأ مجلد "عام" افتراضي. ✅
- [x] نقطة نهاية `GET /properties/{propertyId}/files` تُرجع محتويات مجلد العقار للمالك فقط. ✅
- [x] `composer pint` يمر بدون أخطاء. ✅
- [x] اختبارات `PropertyFolderIntegrationTest` تمر بنجاح. ✅

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
