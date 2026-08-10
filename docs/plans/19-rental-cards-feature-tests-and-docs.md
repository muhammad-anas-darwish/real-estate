# المهمة #19: اختبارات قبول شاملة + توثيق Scribe لبطاقات التأجير

> **التقرير المصدر:** `docs/ideas/rental-cards/report.md` (معايير القبول 1–23)
> **الهدف:** اختبارات Feature شاملة تغطي كل معايير القبول الـ 23 + توثيق Scribe لـ API endpoints.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 عالية
> **الجهد المقدّر:** 1.5 – 2 يوم
> **الاعتمادية:** [#15](./15-rental-cards-data-foundation.md) + [#16](./16-rental-cards-service-and-status-integration.md) + [#17](./17-rental-cards-api-surface.md) + [#18](./18-rental-cards-pre-rental-photos.md) — يجب أن تكون كل الميزة جاهزة قبل بدء هذه الخطة.
> **راجع:** `report.md` السطور 175–198 (معايير القبول 1–23 كاملةً)

---

## الوضع الحالي

الخطط #15-#18 تضيف كل الميزة (Model + Service + API + Photos). لكن لا يوجد ملف اختبار feature واحد يربط السيناريوهات بمعايير القبول. كما لا توجد تعليقات Scribe على Controller ليُولِّد `php artisan scribe:generate` توثيقًا صحيحًا.

## المرحلة 1: Feature Tests — التغطية الكاملة (~ 6-8 ساعات)

`Modules/RealEstate/Tests/RentalCardTest.php` — اختبار feature واحد طويل يغطي معظم السيناريوهات. النمط مأخوذ من `PropertyTest.php` و `PropertyViewingTest.php` في نفس المجلد.

### البنية الأساسية

```php
<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Entities\User;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\RentalCardStatus;
use Tests\TestCase;

class RentalCardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherUser;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->property = Property::factory()
            ->for($this->owner, 'publisher')
            ->create(['status' => PropertyStatus::APPROVED]);

        Sanctum::actingAs($this->owner);
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 1 + 9: إنشاء بطاقة لعقار متاح + تحوّل الحالة لـ RENTED
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function owner_can_create_rental_card_for_available_property(): void
    {
        $tenant = User::factory()->create();
        $response = $this->postJson('/api/dashboard/rental-cards', [
            'property_id' => $this->property->id,
            'tenant_user_id' => $tenant->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonths(6)->toDateString(),
            'terms' => 'Standard terms',
            'is_renewable' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.tenant.type', 'registered')
            ->assertJsonPath('data.is_renewable', true);

        $this->assertDatabaseHas('rental_cards', [
            'property_id' => $this->property->id,
            'tenant_user_id' => $tenant->id,
            'status' => 'active',
        ]);

        $this->assertEquals(PropertyStatus::RENTED, $this->property->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 2 + 3 + 4: المستأجر الخارجي
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function owner_can_create_rental_card_for_external_tenant(): void
    {
        $response = $this->postJson('/api/dashboard/rental-cards', [
            'property_id' => $this->property->id,
            'external_tenant_name' => 'محمد العلي',
            'external_tenant_phone' => '+966500000000',
            'external_tenant_email' => 'mohammed@example.com',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonths(6)->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.tenant.type', 'external')
            ->assertJsonPath('data.tenant.name', 'محمد العلي')
            ->assertJsonPath('data.tenant.phone', '+966500000000');
    }

    /** @test */
    public function external_tenant_name_is_required_when_no_user(): void
    {
        $response = $this->postJson('/api/dashboard/rental-cards', [
            'property_id' => $this->property->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonths(6)->toDateString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['external_tenant_name']);
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 5 + 6 + 7: المدة والبنود والملاحظات وقابلية الإعادة
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function terms_and_notes_are_optional_with_defaults(): void
    {
        $tenant = User::factory()->create();
        $response = $this->postJson('/api/dashboard/rental-cards', [
            'property_id' => $this->property->id,
            'tenant_user_id' => $tenant->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonths(6)->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.terms', null)
            ->assertJsonPath('data.notes', null)
            ->assertJsonPath('data.is_renewable', false); // default
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 10: العقار المؤجر لا يظهر للزوار
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function rented_property_does_not_appear_in_public_search(): void
    {
        RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);
        $this->property->update(['status' => PropertyStatus::RENTED]);

        $response = $this->getJson('/api/properties/browse');

        $response->assertOk();
        $response->assertJsonMissing(['id' => $this->property->id]);
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 11: لا يمكن إنشاء بطاقتين نشطتين
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function cannot_create_second_active_card_on_same_property(): void
    {
        RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $tenant = User::factory()->create();
        $response = $this->postJson('/api/dashboard/rental-cards', [
            'property_id' => $this->property->id,
            'tenant_user_id' => $tenant->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonths(6)->toDateString(),
        ]);

        $response->assertUnprocessable();
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 12: العقار المباع لا يقبل تأجير
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function cannot_rent_sold_property(): void
    {
        $this->property->update(['status' => PropertyStatus::SOLD]);
        $tenant = User::factory()->create();

        $response = $this->postJson('/api/dashboard/rental-cards', [
            'property_id' => $this->property->id,
            'tenant_user_id' => $tenant->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonths(6)->toDateString(),
        ]);

        $response->assertUnprocessable();
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 13 + 14: إنهاء مبكر + رجوع العقار لـ APPROVED
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function owner_can_end_rental_card_early(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);
        $this->property->update(['status' => PropertyStatus::RENTED]);

        $response = $this->patchJson("/api/dashboard/rental-cards/{$card->id}/end", [
            'end_reason' => 'Tenant moved abroad',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'ended')
            ->assertJsonPath('data.end_reason', 'Tenant moved abroad');

        $this->assertEquals(PropertyStatus::APPROVED, $this->property->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 15 + 16: التجديد فقط للقابلة للإعادة + تعديل البنود
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function can_renew_only_when_is_renewable(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'is_renewable' => false,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->patchJson("/api/dashboard/rental-cards/{$card->id}/renew", [
            'end_date' => today()->addYear()->toDateString(),
        ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function renew_extends_end_date_and_increments_counter(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'is_renewable' => true,
            'status' => RentalCardStatus::ACTIVE,
            'renewal_count' => 0,
        ]);

        $newEnd = today()->addYear()->toDateString();
        $response = $this->patchJson("/api/dashboard/rental-cards/{$card->id}/renew", [
            'end_date' => $newEnd,
            'notes' => 'Renewed for another year',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.end_date', $newEnd)
            ->assertJsonPath('data.renewal_count', 1)
            ->assertJsonPath('data.notes', 'Renewed for another year');
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 17: لا يمكن تعديل المستأجر أو تاريخ البداية
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function cannot_modify_tenant_or_start_date_after_creation(): void
    {
        $originalTenant = User::factory()->create();
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'tenant_user_id' => $originalTenant->id,
            'start_date' => today(),
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $newTenant = User::factory()->create();
        $response = $this->patchJson("/api/dashboard/rental-cards/{$card->id}", [
            'tenant_user_id' => $newTenant->id,
            'start_date' => today()->addDays(5)->toDateString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_user_id', 'start_date']);
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 18: لا يمكن حذف بطاقة نشطة
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function cannot_delete_active_rental_card(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->deleteJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertUnprocessable();
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 19 + 20: سجل البطاقات السابقة
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function history_shows_all_past_cards_with_terminal_statuses(): void
    {
        RentalCard::factory()->count(2)->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ENDED,
        ]);
        RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::CANCELLED,
        ]);
        RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->getJson("/api/dashboard/properties/{$this->property->id}/rental-cards/history");

        $response->assertOk()
            ->assertJsonCount(4, 'data');
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 21: بيانات المستأجر المسجّل تبقى بعد حذف حسابه
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function card_remains_when_registered_tenant_is_deleted(): void
    {
        $tenant = User::factory()->create();
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'tenant_user_id' => $tenant->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $tenant->delete(); // soft-delete

        $card->refresh();
        $this->assertNotNull($card->tenant_user_id);
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 22: حذف العقار يحذف البطاقات
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function deleting_property_cascades_to_rental_cards(): void
    {
        RentalCard::factory()->count(3)->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $this->property->delete();

        $this->assertDatabaseMissing('rental_cards', [
            'property_id' => $this->property->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 23: الصور تظهر في البطاقة (يعتمد على #18)
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function card_includes_pre_rental_photos_in_resource(): void
    {
        // يتطلب #18 منفّذ
        $this->markTestSkipped('Requires plan #18 to be completed first.');
    }

    // ─────────────────────────────────────────────────────────────
    // معيار 8: إرفاق صور قبل التأجير
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function pre_rental_photos_can_be_attached_to_card(): void
    {
        $this->markTestSkipped('Requires plan #18 to be completed first.');
    }

    // ─────────────────────────────────────────────────────────────
    // Authorization: مالك آخر لا يستطيع عرض بطاقة ليست له
    // ─────────────────────────────────────────────────────────────

    /** @test */
    public function other_user_cannot_view_another_users_rental_card(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->otherUser);
        $response = $this->getJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertForbidden();
    }
}
```

## المرحلة 2: اختبارات الفلترة العامة (~ 2 ساعة)

`Modules/RealEstate/Tests/RentalCardPublicFilterTest.php` — اختبارات مخصّصة لتغطية معيار 10 بشكل أعمق:

```php
/** @test */
public function public_browse_excludes_rented_properties(): void { ... }

/** @test */
public function public_browse_excludes_sold_properties(): void { ... }

/** @test */
public function ad_display_excludes_rented_properties(): void { ... }

/** @test */
public function sponsored_ad_shows_only_available_properties(): void { ... }
```

## المرحلة 3: Scribe Documentation (~ 2 ساعات)

### 3.1 إضافة تعليقات Scribe على Controller

`Modules/RealEstate/Http/Controllers/RentalCardController.php` — إضافة docblocks على كل action:

```php
/**
 * List rental cards.
 *
 * @group Rental Cards
 *
 * @authenticated
 *
 * @queryParam property_id integer Optional. Filter by property. Example: 5
 * @queryParam status string Optional. Filter by status (active, ended, cancelled, renewed).
 * @queryParam is_renewable boolean Optional. Filter by renewability.
 * @queryParam search string Optional. Search by tenant name/phone/email.
 * @queryParam start_date date Optional. Filter cards starting on or after this date.
 * @queryParam end_date date Optional. Filter cards ending on or before this date.
 * @queryParam page integer Optional. Page number. Example: 1
 *
 * @response 200 {
 *   "data": [...],
 *   "pagination": {...}
 * }
 */
public function index(RentalCardFilterRequest $request): JsonResponse
{
    // ...
}
```

نفس النمط لكل action: `store`, `show`, `active`, `history`, `update`, `end`, `renew`, `destroy`.

### 3.2 إضافة Scribe tags

في ملف `config/scribe.php` (إن لزم) أو عبر docblock `@group Rental Cards`، كل البطاقات ستظهر في مجموعة واحدة في التوثيق.

### 3.3 توليد التوثيق

```bash
php artisan scribe:generate
```

يجب أن ينتج ملفات توثيق في `public/docs/` أو ما يعادله.

## المرحلة 4: التحقق النهائي (~ 1 ساعة)

```bash
# تشغيل كل اختبارات الـ RealEstate
php artisan test --testsuite=Modules --filter=RentalCard

# التحقق من تنسيق الكود
composer pint

# التحقق من الـ static analysis (إن وُجد)
./vendor/bin/phpstan analyse Modules/RealEstate

# توليد التوثيق
php artisan scribe:generate
```

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1 (Feature tests) + المرحلة 2 (Public filter tests) | ملفات اختبار مختلفة، لا تعارض | #15, #16, #17 |
| المرحلة 3 (Scribe) | ملف منفصل (Controller) | #17 (Controller) |
| المرحلة 4 (التحقق) | تبع المراحل 1-3 | المراحل 1-3 |

> **ملاحظة:** هذه الخطة يجب ألّا تُنفَّذ قبل #15-#18 لأن كل الاختبارات والتوثيق تعتمد على الميزة الكاملة.

## معايير القبول

- [ ] `php artisan test --filter=RentalCardTest` يمر بنجاح (باستثناء الـ skipped tests قبل #18).
- [ ] `php artisan test --filter=RentalCardPublicFilterTest` يمر بنجاح.
- [ ] كل معيار من معايير القبول 1-23 مغطى باختبار واحد على الأقل (الـ skipped في #18 تُفعّل لاحقًا).
- [ ] توليد Scribe ينتج توثيقًا لكل endpoints الـ 8 لمجموعة "Rental Cards".
- [ ] التوثيق يحوي query parameters, request body, response codes, أمثلة.
- [ ] `composer pint` يمر بدون أخطاء.
- [ ] لا اختبارات معطلة (skipped) غير معلّل بسبب تبعية على خطة #18.
- [ ] تغطية الكود (Code coverage) لطبقات RentalCard ≥ 85%.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
