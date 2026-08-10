# المهمة #23: نظام حصة التخزين وترقية المساحة (Storage Quota System)

> **التقرير المصدر:** `docs/ideas/per-user-file-system/report.md` (السيناريو 10، القواعد 28–34، الحالات الاستثنائية)
> **الهدف:** تتبع المساحة المستهلكة لكل مستخدم، منع رفع الملفات عند تجاوز الحد، عرض مؤشر المساحة + واجهة ترقية المساحة (بدون دفع فعلي).
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 متوسطة
> **الجهد المقدّر:** 1 – 1.5 يوم
> **الاعتمادية:** [#20](./20-per-user-file-system-data-foundation.md) + [#21](./21-per-user-file-system-core-service-and-api.md)
> **راجع:** `report.md` السطور 99–106 (السيناريو 10)، 148–155 (القواعد 28–34)، 183–191 (الحالات الاستثنائية)، 215–221 (معايير القبول)

---

## الوضع الحالي

بعد اكتمال الخطتين #20 و #21، نملك جدول `storage_limits` ونموذج `StorageLimit`، وخدمة `FileService` التي تتعقب المساحة المستهلكة عبر `incrementStorageUsed()` / `decrementStorageUsed()`. لكن:

- لا توجد نقطة نهاية لعرض حالة الحصة التخزينية للمستخدم.
- لا يوجد تحقق من الحصة قبل رفع الملفات (تم تضمينه في `FileService` لكن يحتاج تأكيد).
- لا توجد واجهة لترقية المساحة (اختيار باقة).
- لا يوجد إنشاء تلقائي لسجل `StorageLimit` عند تسجيل المستخدم.
- لا يوجد معالجة لانتهاء صلاحية الباقات المدفوعة.

هذه الخطة تُكمل نظام الحصة التخزينية وتضيف سطح API لإدارته.

---

## المرحلة 1: إنشاء `StorageLimit` تلقائيًا عند التسجيل (~ 1 ساعة)

**الهدف:** إنشاء سجل حصة افتراضي (100 MB مجاني) لكل مستخدم جديد.

### 1.1 مستمع (Listener)

`Modules/FileSystem/Listeners/CreateStorageLimit.php`:

```php
<?php

namespace Modules\FileSystem\Listeners;

use Modules\Auth\Events\UserRegistered;
use Modules\FileSystem\Entities\StorageLimit;

class CreateStorageLimit
{
    public function handle(UserRegistered $event): void
    {
        StorageLimit::firstOrCreate(
            ['user_id' => $event->user->id],
            [
                'quota_bytes' => 104_857_600, // 100 MB
                'used_bytes' => 0,
                'package_type' => 'free',
                'package_expires_at' => null, // الباقة المجانية لا تنتهي
            ]
        );
    }
}
```

### 1.2 تسجيل المستمع

في `FileSystemServiceProvider::boot()` (إضافة إلى المستمعين السابقين):

```php
Event::listen(UserRegistered::class, CreateStorageLimit::class);
```

---

## المرحلة 2: `StorageQuotaService` (~ 2-3 ساعات)

**الهدف:** خدمة تدير حالة الحصة وعمليات الترقية.

`Modules/FileSystem/Services/StorageQuotaService.php`:

```php
<?php

namespace Modules\FileSystem\Services;

use App\Services\BaseService;
use Modules\FileSystem\Entities\StorageLimit;
use Modules\FileSystem\Enums\StoragePackageType;

class StorageQuotaService extends BaseService
{
    protected const CACHE_TAG = 'storage_quota';

    public function getLimit(int $userId): StorageLimit
    {
        return StorageLimit::forUser($userId)->firstOrFail();
    }

    public function getStatus(int $userId): array
    {
        $limit = $this->getLimit($userId);

        return [
            'user_id' => $limit->user_id,
            'quota_bytes' => $limit->quota_bytes,
            'quota_readable' => $this->formatBytes($limit->quota_bytes),
            'used_bytes' => $limit->used_bytes,
            'used_readable' => $this->formatBytes($limit->used_bytes),
            'remaining_bytes' => $limit->remaining_bytes,
            'remaining_readable' => $this->formatBytes($limit->remaining_bytes),
            'used_percentage' => $limit->used_percentage,
            'package_type' => $limit->package_type?->value,
            'package_expires_at' => $limit->package_expires_at?->format('Y-m-d H:i:s'),
            'is_exceeded' => $limit->isExceeded(),
            'is_near_limit' => $limit->isNearLimit(),
            'can_upload' => !$limit->isExceeded(),
            'available_packages' => $this->getAvailablePackages($limit),
        ];
    }

    /**
     * قائمة الباقات المتاحة للترقية (تظهر الباقات الأعلى من الحالية فقط)
     */
    public function getAvailablePackages(StorageLimit $limit): array
    {
        $current = $limit->package_type;
        $packages = [];

        foreach (StoragePackageType::cases() as $type) {
            if ($type->quotaBytes() <= $current->quotaBytes()) {
                continue;
            }
            $packages[] = [
                'type' => $type->value,
                'label' => $type->label(),
                'quota_bytes' => $type->quotaBytes(),
                'quota_readable' => $this->formatBytes($type->quotaBytes()),
            ];
        }

        return $packages;
    }

    /**
     * ترقية المساحة إلى باقة جديدة (منطق الحجز — الدفع الفعلي مؤجل)
     *
     * في النسخة الحالية: تُطبَّق الترقية مباشرةً.
     * عند ربط نظام الاشتراكات لاحقًا: تُستبدل هذه الدالة بمنطق الدفع.
     */
    public function upgrade(int $userId, StoragePackageType $newPackage): StorageLimit
    {
        $limit = $this->getLimit($userId);

        if ($newPackage->quotaBytes() <= $limit->package_type->quotaBytes()) {
            throw new \InvalidArgumentException(__('messages.package_must_be_higher'));
        }

        if ($newPackage === StoragePackageType::MAX && $limit->quota_bytes >= StoragePackageType::MAX->quotaBytes()) {
            throw new \InvalidArgumentException(__('messages.max_storage_reached'));
        }

        $limit->update([
            'quota_bytes' => $newPackage->quotaBytes(),
            'package_type' => $newPackage,
        ]);

        $this->clearCache();
        return $limit->fresh();
    }

    /**
     * إعادة تعيين الحصة إلى المجانية عند انتهاء الباقة المدفوعة
     */
    public function resetToFree(int $userId): StorageLimit
    {
        $limit = $this->getLimit($userId);
        $freeType = StoragePackageType::FREE;

        $limit->update([
            'quota_bytes' => $freeType->quotaBytes(),
            'package_type' => $freeType,
            'package_expires_at' => null,
        ]);

        $this->clearCache();
        return $limit->fresh();
    }

    /**
     * التحقق من صلاحية الباقات المنتهية (يمكن استدعاؤها عبر Scheduler)
     */
    public function expireStalePackages(): int
    {
        $expired = StorageLimit::where('package_expires_at', '<', now())
            ->where('package_type', '!=', StoragePackageType::FREE)
            ->get();

        $count = 0;
        foreach ($expired as $limit) {
            $this->resetToFree($limit->user_id);
            $count++;
        }

        return $count;
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

---

## المرحلة 3: Controller + Routes للمساحة التخزينية (~ 1.5-2 ساعات)

### 3.1 `StorageQuotaController`

`Modules/FileSystem/Http/Controllers/StorageQuotaController.php`:

```php
<?php

namespace Modules\FileSystem\Http\Controllers;

use App\Models\Controller;
use Illuminate\Http\JsonResponse;
use Modules\FileSystem\Enums\StoragePackageType;
use Modules\FileSystem\Http\Requests\UpgradeStorageRequest;
use Modules\FileSystem\Services\StorageQuotaService;

class StorageQuotaController extends Controller
{
    public function __construct(
        private StorageQuotaService $service,
    ) {}

    /**
     * عرض حالة الحصة التخزينية للمستخدم الحالي
     */
    public function status(): JsonResponse
    {
        $status = $this->service->getStatus(auth()->id());
        return $this->successResponse($status, 'Storage quota status');
    }

    /**
     * قائمة الباقات المتاحة للترقية
     */
    public function packages(): JsonResponse
    {
        $limit = $this->service->getLimit(auth()->id());
        $packages = $this->service->getAvailablePackages($limit);
        return $this->successResponse(['packages' => $packages], 'Available packages');
    }

    /**
     * ترقية المساحة
     */
    public function upgrade(UpgradeStorageRequest $request): JsonResponse
    {
        $packageType = StoragePackageType::from($request->validated('package_type'));
        $limit = $this->service->upgrade(auth()->id(), $packageType);
        return $this->successResponse($limit, 'Storage upgraded')->updated('Storage');
    }
}
```

### 3.2 FormRequest

`Modules/FileSystem/Http/Requests/UpgradeStorageRequest.php`:

```php
<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FileSystem\Enums\StoragePackageType;

class UpgradeStorageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.quota');
    }

    public function rules(): array
    {
        return [
            'package_type' => [
                'required', 'string',
                Rule::in(StoragePackageType::values()),
            ],
        ];
    }
}
```

### 3.3 Routes

إضافة إلى `Modules/FileSystem/Routes/api.php`:

```php
// ─── المساحة التخزينية ─────────────────────────
Route::get('storage/status', [StorageQuotaController::class, 'status'])->name('storage.status');
Route::get('storage/packages', [StorageQuotaController::class, 'packages'])->name('storage.packages');
Route::post('storage/upgrade', [StorageQuotaController::class, 'upgrade'])->name('storage.upgrade');
```

---

## المرحلة 4: تحسين تجربة رفع الصور مع الحصة (~ 1 ساعة)

**الهدف:** إرجاع معلومات الحصة مع ردود رفع الصور، وإظهار تحذيرات عند الاقتراب من الحد.

### 4.1 إضافة معلومات الحصة إلى رد `FileController::uploadImage()`

تعديل دالة `uploadImage` في `FileController`:

```php
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

    // إرفاق حالة الحصة مع الرد
    $quotaService = app(StorageQuotaService::class);
    $quotaStatus = $quotaService->getStatus(auth()->id());

    return $this->successResponse([
        'file' => $file,
        'storage' => $quotaStatus,
    ], 'Image uploaded')->created('File');
}
```

### 4.2 إضافة معلومات الحصة إلى `FolderController::show()`

تعديل دالة `show` لإرفاق حالة الحصة:

```php
public function show(int $id): JsonResponse
{
    $contents = $this->service->contents($id, auth()->id());
    $quotaService = app(StorageQuotaService::class);
    $contents['storage'] = $quotaService->getStatus(auth()->id());

    return $this->successResponse($contents, 'Folder contents retrieved');
}
```

---

## المرحلة 5: رسائل الترجمة (~ 15 دقيقة)

`lang/en/messages.php`:

```php
'package_must_be_higher' => 'The selected package must offer more storage than your current plan.',
'max_storage_reached' => 'You have reached the maximum storage limit (5 GB).',
'storage_upgraded' => 'Storage plan upgraded successfully.',
'storage_reset_to_free' => 'Your storage plan has been reset to the free tier.',
```

`lang/ar/messages.php`:

```php
'package_must_be_higher' => 'يجب أن تقدّم الباقة المختارة مساحة أكبر من باقتك الحالية.',
'max_storage_reached' => 'لقد وصلت للحدّ الأقصى للمساحة (5 غيغابايت).',
'storage_upgraded' => 'تمت ترقية باقتك التخزينية بنجاح.',
'storage_reset_to_free' => 'تم إعادة باقتك التخزينية إلى المستوى المجاني.',
```

---

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1 + 5 | مستمع بسيط + ترجمة — مستقلان تمامًا | لا شيء |
| المرحلة 2 | Service مستقل | لا شيء |
| المرحلة 3 + 4 | تعتمدان على المرحلة 2 | المرحلة 2 |

---

## معايير القبول

- [ ] `GET /api/storage/status` يُرجع `quota_bytes`, `used_bytes`, `remaining_bytes`, `used_percentage`, `is_exceeded`, `is_near_limit`, `can_upload`, `available_packages`.
- [ ] `GET /api/storage/packages` يُرجع الباقات الأعلى من الحالية فقط.
- [ ] `POST /api/storage/upgrade` يرفع الحصة إلى الباقة المختارة.
- [ ] `POST /api/storage/upgrade` يرفض الترقية إذا كانت الباقة أقل من أو تساوي الحالية.
- [ ] `POST /api/storage/upgrade` يرفض الترقية إذا كان المستخدم على الحد الأقصى (MAX).
- [ ] عند إنشاء مستخدم جديد، يُنشأ `StorageLimit` تلقائيًا بـ 100 MB مجاني.
- [ ] خدمة `StorageQuotaService::expireStalePackages()` تعيد الباقات المنتهية إلى FREE.
- [ ] رد رفع الصورة يحتوي على `storage` بحالة الحصة بعد الرفع.
- [ ] رد عرض محتويات المجلد يحتوي على `storage` بحالة الحصة.
- [ ] عند بلوغ 80% من الحصة، `is_near_limit = true`.
- [ ] عند بلوغ 100% من الحصة، `is_exceeded = true` و `can_upload = false`.
- [ ] `FileService::assertQuotaAvailable()` يرفض رفع الملفات عند امتلاء الحصة برسالة واضحة.
- [x] مرحلة 1: إنشاء `StorageLimit` تلقائيًا عند التسجيل ✅
- [x] مرحلة 2: `StorageQuotaService` ✅
- [x] مرحلة 3: Controller + Routes للمساحة التخزينية ✅
- [x] مرحلة 4: تحسين تجربة رفع الصور مع الحصة ✅
- [x] مرحلة 5: رسائل الترجمة ✅
- [x] `composer pint` يمر بدون أخطاء. ✅

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
