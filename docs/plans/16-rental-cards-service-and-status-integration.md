# المهمة #16: خدمة بطاقات التأجير + ربط حالة العقار (Service + Status Integration)

> **التقرير المصدر:** `docs/ideas/rental-cards/report.md` (القسم: القواعد 1–11، السيناريوهات 1–7، الحالات الاستثنائية)
> **الهدف:** بناء `RentalCardService` (CRUD + lifecycle: end/renew) + إضافة `RENTED` لـ `PropertyStatus` + فلترة العقارات المتاحة من نتائج البحث العام + تنظيف تلقائي عند حذف العقار.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🔴 عالية
> **الجهد المقدّر:** 1.5 – 2 يوم
> **الاعتمادية:** [#15](./15-rental-cards-data-foundation.md) — يجب أن تكون Migration + Model + Enum جاهزة.
> **راجع:** `report.md` السطور 90–103 (القواعد 1–11), 161–173 (الحالات الاستثنائية), 184–189 (معايير القبول 9–14).

---

## الوضع الحالي

لا توجد خدمة (`Service`) تتعامل مع دورة حياة بطاقة التأجير. `PropertyStatus` enum يحوي 8 حالات إدارية (PENDING, UNDER_INSPECTION, APPROVED, REJECTED, SUSPENDED, SOLD, ARCHIVED, DRAFT) في `Modules\RealEstate\Enums\PropertyStatus.php:5-14` — ولا يوجد مفهوم "مُؤجَّر". عند البحث العام عن عقار (`PropertyController::indexPublic` في `Modules/RealEstate/Routes/api.php:49`)، لا يوجد أي فلتر يستثني العقارات المؤجّرة. هذه الخطة تسدّ هذه الفجوات.

## المرحلة 1: إضافة `RENTED` لـ `PropertyStatus` enum (~ 30 دقيقة)

`Modules/RealEstate/Enums/PropertyStatus.php` — إضافة الحالة الجديدة:

```php
case RENTED = 'rented';
```

ودالة `label()` المقابلة:

```php
self::RENTED => 'Rented',
```

**ثم يجب تحديث كل `match` يستخدم `PropertyStatus`** في `Modules\RealEstate/Services/PropertyStatusService.php` لإضافة فرع `PropertyStatus::RENTED` يُرجع `true` أو يرمي — حسب السلوك المطلوب (الانتقال إلى RENTED لا يحدث عبر `PropertyStatusService::handle()` بل عبر `RentalCardService::create()` مباشرة، فيُكتفى بعدم إضافة فرع والسماح بحدوث خطأ صامت في حالة استدعاء خاطئ).

> **ملاحظة حرجة:** قاعدة `report.md:103`: "تغيير حالة العقار يحدث من النظام تلقائيًا ولا يستطيع المالك تبديلها يدويًا بين متاح ومُؤجَّر". هذا يعني: المالك لا يستدعي `updateStatus('rented')`، بل `RentalCardService` يتولّى المهمة ويُغيّر `Property::status` مباشرة.

## المرحلة 2: `RentalCardService` (~ 6-8 ساعات)

`Modules/RealEstate/Services/RentalCardService.php` — الخدمة الرئيسية:

```php
<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\DTOs\CreateRentalCardDTO;
use Modules\RealEstate\DTOs\EndRentalCardDTO;
use Modules\RealEstate\DTOs\RenewRentalCardDTO;
use Modules\RealEstate\DTOs\UpdateRentalCardDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\RentalCardStatus;

class RentalCardService extends BaseService
{
    protected const CACHE_TAG = 'rental_cards';

    protected const MAX_PRE_RENTAL_PHOTOS = 20;

    public function list(?int $propertyId = null, ?int $ownerId = null): LengthAwarePaginator
    {
        $query = RentalCard::query()
            ->filter()
            ->with(['property', 'owner', 'tenantUser', 'endedBy']);

        if ($propertyId) {
            $query->forProperty($propertyId);
        }

        if ($ownerId) {
            $query->forOwner($ownerId);
        }

        return $query
            ->orderBy(request('sort_by', 'created_at'), request('sort_order', 'desc'))
            ->paginate($this->getPerPage());
    }

    public function history(int $propertyId): LengthAwarePaginator
    {
        return $this->list($propertyId);
    }

    public function activeForProperty(int $propertyId): ?RentalCard
    {
        return RentalCard::forProperty($propertyId)
            ->active()
            ->with(['property', 'owner', 'tenantUser'])
            ->latest('start_date')
            ->first();
    }

    public function find(int $id): RentalCard
    {
        return RentalCard::with(['property', 'owner', 'tenantUser', 'endedBy'])
            ->findOrFail($id);
    }

    public function create(CreateRentalCardDTO $dto): RentalCard
    {
        return DB::transaction(function () use ($dto) {
            $property = Property::findOrFail($dto->property_id);

            $this->assertCanRent($property, $dto->owner_id);
            $this->assertDatesValid($dto->start_date, $dto->end_date);
            $this->assertTenantProvided($dto);

            $card = RentalCard::create(array_merge($dto->toArray(), [
                'status' => RentalCardStatus::ACTIVE,
            ]));

            $property->update(['status' => PropertyStatus::RENTED]);
            $this->clearCache();

            return $card->fresh(['property', 'owner', 'tenantUser']);
        });
    }

    public function update(int $id, UpdateRentalCardDTO $dto): RentalCard
    {
        return DB::transaction(function () use ($id, $dto) {
            $card = $this->find($id);

            $this->assertOwner($card);
            $this->assertActive($card);

            $card->update($dto->toArray());
            $this->clearCache();

            return $card->fresh(['property', 'owner', 'tenantUser']);
        });
    }

    public function end(int $id, EndRentalCardDTO $dto): RentalCard
    {
        return DB::transaction(function () use ($id, $dto) {
            $card = $this->find($id);

            $this->assertOwner($card);
            $this->assertActive($card);

            $card->update([
                'status' => RentalCardStatus::ENDED,
                'ended_at' => $dto->ended_at ?? now(),
                'end_reason' => $dto->end_reason,
                'ended_by' => Auth::id(),
            ]);

            $this->restorePropertyToAvailable($card->property_id);
            $this->clearCache();

            return $card->fresh(['property', 'owner', 'tenantUser', 'endedBy']);
        });
    }

    public function renew(int $id, RenewRentalCardDTO $dto): RentalCard
    {
        return DB::transaction(function () use ($id, $dto) {
            $card = $this->find($id);

            $this->assertOwner($card);
            $this->assertActive($card);

            if (! $card->is_renewable) {
                throw new \RuntimeException(__('messages.rental_card_not_renewable'));
            }

            $this->assertDatesValid($dto->start_date ?? $card->start_date->format('Y-m-d'), $dto->end_date);

            $card->update([
                'start_date' => $dto->start_date ?? $card->start_date,
                'end_date' => $dto->end_date,
                'terms' => $dto->terms ?? $card->terms,
                'notes' => $dto->notes ?? $card->notes,
                'is_renewable' => $dto->is_renewable ?? $card->is_renewable,
                'renewed_at' => now(),
                'renewal_count' => $card->renewal_count + 1,
                'status' => RentalCardStatus::ACTIVE,
            ]);

            $this->clearCache();

            return $card->fresh(['property', 'owner', 'tenantUser']);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $card = $this->find($id);

            $this->assertOwner($card);

            if ($card->status === RentalCardStatus::ACTIVE) {
                throw new \RuntimeException(__('messages.rental_card_cannot_delete_active'));
            }

            $card->delete();
            $this->clearCache();
        });
    }

    // ─── قواعد التحقق (Private) ──────────────────────────────────────

    private function assertCanRent(Property $property, int $ownerId): void
    {
        if ((int) $property->publisher_id !== $ownerId) {
            throw new \RuntimeException(__('messages.rental_card_not_owner'));
        }

        if ($property->status === PropertyStatus::SOLD) {
            throw new \RuntimeException(__('messages.rental_card_property_sold'));
        }

        if ($property->status !== PropertyStatus::APPROVED) {
            throw new \RuntimeException(__('messages.rental_card_property_not_available'));
        }

        if (RentalCard::forProperty($property->id)->active()->exists()) {
            throw new \RuntimeException(__('messages.rental_card_active_exists'));
        }
    }

    private function assertDatesValid(string $start, string $end): void
    {
        $startDate = \Carbon\Carbon::parse($start);
        $endDate = \Carbon\Carbon::parse($end);

        if ($endDate->lt($startDate)) {
            throw new \RuntimeException(__('messages.rental_card_end_before_start'));
        }
    }

    private function assertTenantProvided(CreateRentalCardDTO $dto): void
    {
        if ($dto->tenant_user_id) {
            return; // مستأجر مسجّل — لا حاجة لمزيد من التحقق
        }

        if (! $dto->external_tenant_name) {
            throw new \RuntimeException(__('messages.rental_card_external_tenant_name_required'));
        }
    }

    private function assertOwner(RentalCard $card): void
    {
        if ((int) $card->owner_id !== (int) Auth::id() && ! Auth::user()?->hasRole('super-admin')) {
            throw new \RuntimeException(__('messages.rental_card_not_owner'));
        }
    }

    private function assertActive(RentalCard $card): void
    {
        if ($card->status !== RentalCardStatus::ACTIVE) {
            throw new \RuntimeException(__('messages.rental_card_not_active'));
        }
    }

    private function restorePropertyToAvailable(int $propertyId): void
    {
        $property = Property::find($propertyId);

        if (! $property) {
            return;
        }

        // لا نُعيد العقار إلى APPROVED إذا كان مُباعًا (لا يحدث، لكن للأمان)
        if ($property->status === PropertyStatus::SOLD) {
            return;
        }

        $property->update(['status' => PropertyStatus::APPROVED]);
    }
}
```

## المرحلة 3: تنظيف البطاقات عند حذف العقار (~ 1 ساعة)

`Modules/RealEstate/Entities/Property.php` — إضافة `deleting` event:

```php
protected static function booted(): void
{
    parent::booted();

    static::deleting(function (Property $property) {
        if ($property->isForceDeleting()) {
            $property->rentalCards()->delete();
        }
    });
}
```

ودالة العلاقة في `Property`:

```php
public function rentalCards(): HasMany
{
    return $this->hasMany(RentalCard::class);
}

public function activeRentalCard(): HasOne
{
    return $this->hasOne(RentalCard::class)->where('status', RentalCardStatus::ACTIVE);
}
```

> **ملاحظة:** الـ cascade على مستوى قاعدة البيانات (في الـ migration من خطة #15) يحذف البطاقات عند حذف العقار، لكن يجب التأكد أن `Property::delete()` يستخدم `forceDelete` (soft-delete) أم hard-delete. السلوك الحالي في `PropertyController::destroy` سيُحدّد ذلك.

## المرحلة 4: فلترة العقارات المتاحة من البحث العام (~ 2 ساعات)

### 4.1 تحديث `PropertyController::indexPublic`

`Modules/RealEstate/Http/Controllers/PropertyController.php` — في الدالة `indexPublic`، إضافة فلتر:

```php
public function indexPublic(AdvancedPropertyFilterRequest $request)
{
    $query = Property::query()
        ->filter()
        ->where('status', PropertyStatus::APPROVED) // لا يظهر مُؤجَّر (RENTED) أو مُباع (SOLD) أو مرفوض
        ->with([...]);
    // ...
}
```

> إذا كان `PropertyService::listPublic` هو المستخدم (وليس الـ controller مباشرةً)، فالفلتر يُضاف في الـ service.

### 4.2 تحديث `AdDisplayService` و `SponsoredAdService`

إذا كانت الإعلانات المعروضة مرتبطة بعقار، يجب التأكد أنها لا تُظهر عقارات مُؤجَّرة. نمط الفحص: `Property::where('status', PropertyStatus::APPROVED)` في كل query.

### 4.3 تحديث `Property::scopeAvailable()` (اختياري)

في `Modules/RealEstate/Entities/Property.php`:

```php
public function scopeAvailable($query)
{
    return $query->where('status', PropertyStatus::APPROVED);
}
```

ليس إلزاميًا، لكنه يُحسّن قابلية القراءة ويُوحّد المنطق.

## المرحلة 5: اختبارات الوحدة للـ Service (~ 2-3 ساعات)

`Modules/RealEstate/Tests/RentalCardServiceTest.php`:

```php
<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\RentalCardStatus;
use Modules\RealEstate\Services\RentalCardService;
use Tests\TestCase;

class RentalCardServiceTest extends TestCase
{
    use RefreshDatabase;

    private RentalCardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RentalCardService::class);
    }

    /** @test */
    public function it_creates_a_card_and_marks_property_as_rented(): void
    {
        // ... اختبارات معايير القبول 1, 9
    }

    /** @test */
    public function it_rejects_creation_when_property_is_sold(): void
    {
        // معيار القبول 12
    }

    /** @test */
    public function it_rejects_creation_when_active_card_exists(): void
    {
        // معيار القبول 11
    }

    /** @test */
    public function it_ends_card_and_restores_property_to_approved(): void
    {
        // معيار القبول 13, 14
    }

    /** @test */
    public function it_renews_card_only_when_is_renewable(): void
    {
        // معيار القبول 15, 16
    }

    /** @test */
    public function it_rejects_end_date_before_start_date(): void
    {
        // حالة استثنائية السطر 164
    }

    /** @test */
    public function it_deletes_cards_when_property_is_force_deleted(): void
    {
        // معيار القبول 22
    }

    /** @test */
    public function external_tenant_requires_name(): void
    {
        // معيار القبول 4
    }
}
```

## المرحلة 6: رسائل الخطأ في `lang/ar/messages.php` (~ 30 دقيقة)

إضافة المفاتيح:

```php
'rental_card_not_owner' => 'غير مصرّح لك بإنشاء بطاقة تأجير على هذا العقار.',
'rental_card_property_sold' => 'العقار مُباع ولا يمكن تأجيره.',
'rental_card_property_not_available' => 'العقار غير متاح للتأجير.',
'rental_card_active_exists' => 'يوجد بطاقة نشطة على هذا العقار، أنهِها أولًا أو انتظر انتهائها.',
'rental_card_end_before_start' => 'تاريخ نهاية التأجير يجب أن يكون بعد أو يساوي تاريخ البداية.',
'rental_card_external_tenant_name_required' => 'اسم المستأجر الخارجي إلزامي.',
'rental_card_not_active' => 'البطاقة ليست نشطة.',
'rental_card_not_renewable' => 'هذه البطاقة غير مميّزة كقابلة لإعادة التأجير.',
'rental_card_cannot_delete_active' => 'لا يمكن حذف بطاقة نشطة — أنهِها أولًا.',
```

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1 + 2 | مستقلّتان، يمكن لأي مطوّر البدء بالمرحلة 1 وآخر بالمرحلة 2 | لا شيء (تحتاج #15) |
| المرحلة 3 + 4 | لا تعارض في الملفات | المرحلة 1 (تحتاج RENTED في enum) |
| المرحلة 5 (Unit tests) + المرحلة 6 (i18n) | لا تعارض | المراحل 1-4 (تحتاج الـ service) |

> **ملاحظة:** هذه الخطة لا يمكن أن تعمل بالتوازي مع #17 (API surface) لأن #17 يعتمد على `RentalCardService`.

## معايير القبول

- [ ] `PropertyStatus::RENTED` مضاف إلى الـ enum، ودالة `label()` تُرجع 'Rented'.
- [ ] `RentalCardService::create()` ينشئ بطاقة نشطة، ويُغيّر `Property::status` إلى RENTED.
- [ ] `RentalCardService::create()` يرمي عند: العقار مُباع، العقار غير APPROVED، يوجد بطاقة نشطة، تاريخ نهاية قبل بداية، مستأجر خارجي بدون اسم.
- [ ] `RentalCardService::end()` يُغلق البطاقة، ويُعيد العقار إلى APPROVED.
- [ ] `RentalCardService::end()` يرمي عند محاولة إنهاء بطاقة غير نشطة.
- [ ] `RentalCardService::renew()` يمدّد `end_date` ويزيد `renewal_count`، ويرمي إذا `is_renewable = false`.
- [ ] `RentalCardService::update()` لا يسمح بتعديل `property_id`, `owner_id`, `tenant_user_id`, `start_date`.
- [ ] `RentalCardService::delete()` يرمي عند محاولة حذف بطاقة نشطة.
- [ ] `Property::deleting` event يحذف جميع بطاقات التأجير المرتبطة (force delete).
- [ ] `Property::rentalCards()` و `Property::activeRentalCard()` يعملان.
- [ ] `PropertyController::indexPublic` لا يُظهر عقارات بحالة RENTED أو SOLD.
- [ ] `AdDisplayService` و `SponsoredAdService` لا يُظهران عقارات مُؤجَّرة.
- [ ] كل رسائل الخطأ موجودة في `lang/ar/messages.php`.
- [ ] كل اختبارات الوحدة في `RentalCardServiceTest` تمر.
- [ ] `composer pint` يمر بدون أخطاء.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
