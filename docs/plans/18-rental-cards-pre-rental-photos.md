# المهمة #18: صور "قبل التأجير" عبر نظام الملفات (Pre-Rental Photos Integration)

> **التقرير المصدر:** `docs/ideas/rental-cards/report.md` (القاعدة 24–27، السيناريو 1 الخطوة 8، معيار القبول 8 و 23)
> **الهدف:** ربط بطاقات التأجير بنظام الملفات لكل مستخدم (`per-user-file-system`) لتخزين صور "قبل التأجير" داخل مجلد العقار، وعرضها في البطاقة.
> **الحالة:** ❌ لم يبدأ
> **الأولوية:** 🟡 متوسطة (يمكن تأجيلها — الميزة الأساسية تبقى قابلة للعمل بدون صور)
> **الجهد المقدّر:** 1 – 1.5 يوم
> **الاعتمادية:** [#15](./15-rental-cards-data-foundation.md) + [#17](./17-rental-cards-api-surface.md) + ميزة `per-user-file-system` (لا تزال فكرة، يجب تنفيذها أولًا)
> **راجع:** `report.md` السطور 123–127 (القواعد 24–27), 36, 146 (السيناريو 1 الخطوة 8), 183, 198 (معايير القبول 8, 23)

---

## الوضع الحالي

بطاقة التأجير (بعد #15-#17) تعمل بالكامل، لكن لا يوجد مكان لربط صور "قبل التأجير". `CreateRentalCardRequest` يحوي حقل `pre_rental_photo_ids` لكنه غير مربوط بأي نظام بعد. التقرير يفترض وجود نظام ملفات لكل مستخدم (`docs/ideas/per-user-file-system/report.md` — فكرة منفصلة لم تُنفَّذ بعد).

**حالة التبعية:** إذا لم تكن ميزة `per-user-file-system` منفّذة، يجب تنفيذها أولًا أو تأجيل هذه الخطة.

## المرحلة 1: تأكيد جاهزية نظام الملفات (~ 30 دقيقة)

**قبل البدء:** التأكد أن نظام الملفات منفّذ ويوفّر:
- رفع صور بمعرّفات (IDs) يُمكن الرجوع إليها
- فلترة الصور حسب المالك (مالك العقار فقط)
- علاقة عكسية من ملف إلى العقار/الكيان المرتبط

إذا لم يكن جاهزًا، يُرجأ تنفيذ هذه الخطة.

## المرحلة 2: pivot table لربط البطاقة بالملفات (~ 1-2 ساعات)

`Modules/RealEstate/Database/Migrations/2026_06_28_000001_create_rental_card_media_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_card_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rental_card_id')
                ->constrained('rental_cards')
                ->cascadeOnDelete();

            // معرّف الملف من نظام الملفات (جدول `files` أو ما يعادله حسب النظام)
            $table->foreignId('file_id')
                ->constrained('files') // اسم الجدول حسب نظام الملفات
                ->cascadeOnDelete();

            // نوع الوسوم (Tag) لتمييز صور "قبل التأجير" عن غيرها
            $table->string('tag')->default('pre_rental');

            // ترتيب العرض
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['rental_card_id', 'file_id']);
            $table->index('tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_card_media');
    }
};
```

**قرار معماري:** نستخدم pivot table بدلاً من إضافة عمود `pre_rental_photo_ids` (JSON) لأن:
- يدعم الاستعلامات العلائقية
- يحافظ على التكامل المرجعي
- يسمح بحذف تلقائي للعلاقة عند حذف الملف أو البطاقة
- يمكن توسيعه لاحقًا (مثلاً: `post_rental_photos` بتغيير قيمة `tag`)

## المرحلة 3: Model + العلاقات (~ 1 ساعة)

`Modules/RealEstate/Entities/RentalCard.php` — إضافة:

```php
public function media(): BelongsToMany
{
    return $this->belongsToMany(\Modules\FileSystem\Entities\File::class, 'rental_card_media')
        ->withPivot(['tag', 'sort_order'])
        ->withTimestamps()
        ->orderBy('rental_card_media.sort_order');
}

public function preRentalPhotos(): BelongsToMany
{
    return $this->media()->wherePivot('tag', 'pre_rental');
}
```

`Modules/FileSystem/Entities/File.php` (إن لم يكن موجودًا) — إضافة علاقة عكسية:

```php
public function rentalCards(): BelongsToMany
{
    return $this->belongsToMany(RentalCard::class, 'rental_card_media')
        ->withPivot(['tag', 'sort_order'])
        ->withTimestamps();
}
```

> **ملاحظة:** يجب التأكد أن `Files` يُحذف ملفاته نهائيًا عند حذفه (cascade).

## المرحلة 4: تحديث الـ Service (~ 2 ساعات)

`Modules/RealEstate/Services/RentalCardService.php` — في `create()`:

```php
public function create(CreateRentalCardDTO $dto): RentalCard
{
    return DB::transaction(function () use ($dto) {
        // ... (نفس المنطق الحالي)

        $card = RentalCard::create([...]);

        $this->attachPhotos($card, $dto->pre_rental_photo_ids);
        $property->update(['status' => PropertyStatus::RENTED]);
        $this->clearCache();

        return $card->fresh(['property', 'owner', 'tenantUser', 'media']);
    });
}

private function attachPhotos(RentalCard $card, array $fileIds): void
{
    if (empty($fileIds)) {
        return;
    }

    if (count($fileIds) > self::MAX_PRE_RENTAL_PHOTOS) {
        throw new \RuntimeException(__('messages.rental_card_too_many_photos', [
            'max' => self::MAX_PRE_RENTAL_PHOTOS,
        ]));
    }

    // التحقق أن كل الملفات مملوكة لمالك البطاقة وتنتمي لمجلد العقار
    $validFiles = \Modules\FileSystem\Entities\File::query()
        ->whereIn('id', $fileIds)
        ->where('owner_id', $card->owner_id)
        // → تحقق إضافي: الملف داخل مجلد العقار (Folder.parent_id === Property.folder_id)
        ->whereHas('folder', fn ($q) => $q->where('property_id', $card->property_id))
        ->pluck('id')
        ->toArray();

    if (count($validFiles) !== count($fileIds)) {
        throw new \RuntimeException(__('messages.rental_card_invalid_photos'));
    }

    $attachData = [];
    foreach ($fileIds as $index => $fileId) {
        $attachData[$fileId] = [
            'tag' => 'pre_rental',
            'sort_order' => $index,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    $card->media()->attach($attachData);
}
```

**في `delete()`** و `end()`: لا حاجة لفصل — الـ `cascadeOnDelete` على `rental_card_id` في الـ pivot يحذف العلاقات تلقائيًا.

## المرحلة 5: التحقق في FormRequest (~ 1 ساعة)

`Modules/RealEstate/Http/Requests/CreateRentalCardRequest.php` — تخصيص `withValidator` أو `after()`:

```php
public function withValidator($validator): void
{
    $validator->after(function ($v) {
        if ($this->filled('pre_rental_photo_ids')) {
            // التحقق أن كل IDs هي صور موجودة (يمكن في الخدمة أيضًا)
            // يمكن إضافة Rule مخصصة لاحقًا
        }
    });
}
```

التحقق الفعلي للأمان (هل الصور مملوكة للمالك) يتم في الـ Service (المرحلة 4).

## المرحلة 6: تحديث Resource ليشمل الصور (~ 30 دقيقة)

`Modules/RealEstate/Http/Resources/RentalCardResource.php`:

```php
protected function getCustomData(): array
{
    return [
        // ... باقي الحقول
        'pre_rental_photos' => $this->whenLoaded('media', fn () =>
            $this->media->map(fn ($file) => [
                'id' => $file->id,
                'url' => $file->url,
                'thumbnail_url' => $file->thumbnail_url,
                'name' => $file->name,
                'sort_order' => $file->pivot->sort_order,
            ])
        ),
        'pre_rental_photos_count' => $this->whenCounted('media'),
    ];
}
```

وإضافة `getRelationMap`:

```php
protected function getRelationMap(): array
{
    return [
        'property' => PropertyResource::class,
        'owner' => \Modules\Auth\Http\Resources\UserResource::class,
        'tenantUser' => \Modules\Auth\Http\Resources\UserResource::class,
        'endedBy' => \Modules\Auth\Http\Resources\UserResource::class,
        'media' => \Modules\FileSystem\Http\Resources\FileResource::class, // ← جديد
    ];
}
```

## المرحلة 7: اختبارات (~ 2 ساعات)

`Modules/RealEstate/Tests/RentalCardMediaTest.php`:

```php
/** @test */
public function it_attaches_pre_rental_photos_to_card(): void
{
    $owner = User::factory()->create();
    $property = Property::factory()->for($owner, 'publisher')->create();
    $files = File::factory()->count(3)->for($owner, 'owner')->create([
        'folder_id' => $property->folder_id,
    ]);

    $dto = CreateRentalCardDTO::fromRequest([
        'property_id' => $property->id,
        'owner_id' => $owner->id,
        'tenant_user_id' => User::factory()->create()->id,
        'start_date' => today()->toDateString(),
        'end_date' => today()->addMonths(6)->toDateString(),
        'pre_rental_photo_ids' => $files->pluck('id')->toArray(),
    ]);

    $card = app(RentalCardService::class)->create($dto);

    $this->assertCount(3, $card->fresh()->preRentalPhotos);
}

/** @test */
public function it_rejects_photos_not_owned_by_user(): void
{
    // ...
}

/** @test */
public function it_rejects_more_than_max_photos(): void
{
    // 21 صورة → يرمي
}

/** @test */
public function deleting_card_detaches_photos(): void
{
    // pivot يُحذف تلقائيًا
}
```

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1 (التحقق) | لا كود | لا شيء |
| المرحلة 2 (Migration) + المرحلة 3 (Model) | كل ملف مستقل | المرحلة 1 (التأكد من جاهزية نظام الملفات) |
| المرحلة 6 (Resource update) + المرحلة 5 (FormRequest update) | لا تعارض فعلي (ملفات مختلفة) | #17 (الـ Resource و Request الأصليان) |

> **ملاحظة حرجة:** هذه الخطة يجب ألّا تُنفَّذ قبل #15-#17 لأن البطاقة والـ DTO والحقول موجودة هناك. كما أنها تعتمد كليًا على نظام الملفات — إذا لم يكن جاهزًا، تُؤجَّل.

## معايير القبول

- [ ] جدول `rental_card_media` منشأ مع المفاتيح الأجنبية والـ unique constraint.
- [ ] `RentalCard::preRentalPhotos()` يُرجع الصور بوسم `pre_rental` مرتبة بـ `sort_order`.
- [ ] `RentalCard::media()` يُرجع كل الوسائط (مستقبليًا قد يشمل `post_rental`).
- [ ] `RentalCardService::create()` يربط الصور بنجاح ويتحقق من ملكيتها.
- [ ] رفع أكثر من 20 صورة يرمي استثناءً.
- [ ] رفع صورة غير مملوكة لمالك البطاقة يرمي استثناءً.
- [ ] `RentalCardResource` يُرجع `pre_rental_photos` عند تحميل العلاقة.
- [ ] حذف بطاقة (نشطة أو منتهية) يحذف العلاقات في pivot (عبر cascade).
- [ ] حذف ملف من نظام الملفات يحذف العلاقة في pivot.
- [ ] اختبارات `RentalCardMediaTest` تمر.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
