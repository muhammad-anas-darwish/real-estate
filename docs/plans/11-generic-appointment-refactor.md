# المهمة #11: إعادة هيكلة PropertyViewing لتصبح نظام مواعيد عام

| الحقل | القيمة |
|---|---|
| **الحالة** | ❌ لم يبدأ |
| **الأولوية** | 🔴 عالية (تغيير جوهري — يكسر بعض العقود) |
| **الجهد المقدّر** | 8–12 ساعة |
| **الاعتمادية** | لا شيء (تُنفَّذ بالتوازي مع #10) |
| **راجع** | `docs/ideas/mini-crm/report.md` (السطور 76–82، 90–93) |

---

## الوضع الحالي

التقرير يفترض وجود "نظام مواعيد عام" قابل للدمج مع الـ CRM، لكن الموجود هو `Modules/RealEstate/Entities/PropertyViewing.php` بمواصفات:
- `property_id` إلزامي (السطر 23 من `PropertyViewing.php`).
- مرتبط فقط بسياق معاينة العقارات (`agent_id`, `viewing_type`, `max_attendees`).
- لا يدعم ربط الـ polymorphic (followable_type / followable_id) المطلوب لمتابعات الـ CRM.

**القيد الحاسم:** المستخدم اختار "إعادة تسمية PropertyViewing إلى generic appointment" مع وعي بالخطر. سأحافظ على backward compatibility عبر:
1. إضافة أعمدة nullable بدلًا من حذف الموجودة.
2. إبقاء `/api/dashboard/viewings/...` تعمل (لكنها ستشير لنفس الكنترولر المُعاد تسميته).
3. إضافة `/api/dashboard/appointments/...` كـ endpoints عامة.

---

## مراحل التنفيذ

### المرحلة 1 — Migration لتعميم الـ Appointments (1.5 ساعة)
**الملف:** `Modules/RealEstate/Database/Migrations/2026_06_26_100001_generalize_property_viewings_to_appointments.php`

**التغييرات على جدول `property_viewings`:**
```php
public function up(): void
{
    Schema::table('property_viewings', function (Blueprint $table) {
        // إعادة تسمية الجدول للحفاظ على البيانات
        // (لا نزيل الأعمدة القديمة للحفاظ على التوافق)
        $table->string('type')->default('viewing')->after('id')->index();
        // viewing | follow_up | general

        // جعل العقار اختياريًا
        $table->unsignedBigInteger('property_id')->nullable()->change();
        $table->dropForeign(['property_id']);

        // دعم polymorphic
        $table->nullableMorphs('followable');

        // وسيط المتابعة (لـ follow-ups)
        $table->string('contact_method', 32)->nullable()->after('viewing_type');
        // call | whatsapp | visit | email
    });
}

public function down(): void
{
    Schema::table('property_viewings', function (Blueprint $table) {
        $table->dropMorphs('followable');
        $table->dropColumn(['type', 'contact_method']);
        $table->unsignedBigInteger('property_id')->nullable(false)->change();
    });
}
```

**تحذير:** يجب استخدام `doctrine/dbal` لتغيير الـ column type، أو تنفيذ الأمر SQL خام في الـ migration. تأكد من اختبار rollback.

---

### المرحلة 2 — إعادة تسمية الـ Enums (0.5 ساعة)

**الملفات:**

- **جديد:** `Modules/RealEstate/Enums/AppointmentType.php`:
  ```php
  <?php
  namespace Modules\RealEstate\Enums;

  enum AppointmentType: string
  {
      case VIEWING = 'viewing';
      case FOLLOW_UP = 'follow_up';
      case GENERAL = 'general';
  }
  ```
- إبقاء `Modules/RealEstate/Enums/ViewingType.php` كـ alias قديم يستورد من الجديد (backward compat):
  ```php
  <?php
  namespace Modules\RealEstate\Enums;

  // Deprecated: use AppointmentType
  class_alias(AppointmentType::class, 'Modules\\RealEstate\\Enums\\ViewingType');
  ```
  أو بشكل أوضح — استبدل جميع المراجع في الكود بـ `AppointmentType` ثم احذف `ViewingType`.
- `ViewingStatus` يبقى كما هو (الحالات generic بطبيعتها).

---

### المرحلة 3 — نقل الـ Entity والـ Service والـ Controller (2 ساعة)

**الملفات المُنشأة:**
- `Modules/RealEstate/Entities/Appointment.php` — نسخة من `PropertyViewing.php` مع:
  - `$table = 'property_viewings'` (نفس الجدول).
  - إضافة casts لـ `type` و `AppointmentType`.
  - إضافة علاقة `followable()`: `morphTo()`.
  - إضافة scope `ofType(string $type)`.
  - إضافة scope `forUser(int $userId)`.
- `Modules/RealEstate/Services/AppointmentService.php` — منقول من `PropertyViewingService` مع:
  - حقن validation مرنة (property_id مطلوب فقط عند `type=viewing`).
  - إضافة `scheduleFollowUp(LeadFollowUpDTO $dto)`.
  - إضافة `completeWithNote(int $id, ?string $note)`.

**الملفات المحذوفة (بعد التأكد من نقل المنطق):**
- `Modules/RealEstate/Entities/PropertyViewing.php`
- `Modules/RealEstate/Services/PropertyViewingService.php`
- `Modules/RealEstate/Http/Controllers/PropertyViewingController.php`
- `Modules/RealEstate/Database/Factories/PropertyViewingFactory.php` → إعادة تسمية إلى `AppointmentFactory`.

**اختبار المرحلة:** `grep -r "PropertyViewing" --include="*.php" Modules/` يجب أن يعيد صفر نتائج (ما عدا في migration الـ up/down للتوثيق).

---

### المرحلة 4 — تحديث الـ Routes والـ DTOs (1.5 ساعة)

**الملفات:**
- `Modules/RealEstate/Routes/api.php` (السطور 85–108) — استبدال:
  ```php
  // قبل
  Route::get('viewings', [PropertyViewingController::class, 'index']);
  // بعد
  Route::prefix('viewings')->group(function () {
      Route::get('/', [AppointmentController::class, 'indexViewings']);
      Route::post('/', [AppointmentController::class, 'storeViewing']);
      // ... باقي routes الـ viewings
  });
  Route::prefix('appointments')->group(function () {
      Route::get('/', [AppointmentController::class, 'index']);
      Route::post('/', [AppointmentController::class, 'store']);
      // ... باقي routes الـ appointments
  });
  ```
  **(يضمن بقاء URLs القديمة `/dashboard/viewings` تعمل.)**
- `Modules/RealEstate/DTOs/PropertyViewingDTO.php` → إضافة حقل `?string $type`, `?string $contact_method`, `?int $followable_id`, `?string $followable_type`. أو إنشاء `AppointmentDTO` و `LeadFollowUpDTO` منفصلين (مفضّل).
- `Modules/RealEstate/Http/Resources/PropertyViewingResource.php` → إعادة تسمية إلى `AppointmentResource` مع إضافة `type`, `followable`.

---

### المرحلة 5 — تحديث الاختبارات (2 ساعة)
**الملفات:**
- `Modules/RealEstate/Tests/PropertyViewingTest.php` → إعادة تسمية إلى `AppointmentTest.php` مع إضافة:
  - اختبار إنشاء `viewing` بدون `property_id` (يجب أن يفشل بـ validation).
  - اختبار إنشاء `follow_up` مع `property_id = null` و `followable = Lead` (يجب أن ينجح).
  - اختبار scope `ofType('viewing')` و `ofType('follow_up')`.
  - اختبار backward compat: استدعاء `GET /dashboard/viewings` يعيد نفس الـ payload السابق.
- تحديث أي tests أخرى تستورد `PropertyViewing`:
  ```bash
  grep -rln "PropertyViewing" --include="*Test.php"
  ```

---

### المرحلة 6 — تحديث Scribe (1 ساعة)
- حذف output قديم: `rm -rf .scribe/output`.
- `php artisan scribe:generate`.
- التحقق يدويًا من أن endpoint `/viewings` و `/appointments` موثقتان بشكل صحيح.

---

## ⚠️ مخاطر معروفة وكيفيات التخفيف

| المخاطرة | التخفيف |
|---|---|
| كسر URLs قديمة `/viewings/*` | إضافة alias routes تُحوّل لـ `AppointmentController` |
| استيراد `ViewingType` من تطبيق frontend | الإبقاء على class_alias في `Enums/ViewingType.php` لمدة شهر مع warning deprecation |
| كسر relation في Property.php (إن كان يستورد viewings) | بحث شامل قبل الحذف: `grep -rn "hasMany.*PropertyViewing" --include="*.php"` |
| `doctrine/dbal` غير مثبت لتغيير nullable | استخدام `DB::statement('ALTER TABLE ... MODIFY ...')` في الـ migration |

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Migration) | تعدّل الجدول فقط، لا تلمس classes | لا شيء |
| 2 (Enums) | لا تتعارض مع مراحل 1/3/4 | لا شيء |
| 3 (نقل classes) | تعتمد على 1 لاستقرار الـ schema، لكن يمكن البدء بإنشاء الـ classes الجديدة بالتوازي مع 1 | يعتمد على 1 للمرحلة النهائية |
| 4 (Routes) | يجب أن يكون بعد 3 | يعتمد على 3 |
| 5 (Tests) | يجب أن يكون بعد 3 و 4 | يعتمد على 3 و 4 |
| 6 (Scribe) | آخر مرحلة | يعتمد على 5 |

---

## معايير القبول

- [ ] `php artisan migrate` يضيف الأعمدة الجديدة بدون فقدان بيانات.
- [ ] `php artisan migrate:rollback` يعيد الـ schema كما كان (مع فقدان `type` و `contact_method` فقط).
- [ ] `GET /api/dashboard/viewings` لا يزال يعمل ويعيد نفس الـ payload (backward compat).
- [ ] `GET /api/dashboard/appointments` يعمل ويُرجع كل المواعيد (viewings + follow_ups).
- [ ] إنشاء viewing بـ `property_id = null` → 422 validation error.
- [ ] إنشاء follow_up بـ `property_id = null` و `followable = Lead::class` → 201 created.
- [ ] جميع اختبارات `AppointmentTest` تنجح.
- [ ] `grep -r "PropertyViewing" --include="*.php" Modules/` يعيد صفر نتائج.
- [ ] `php artisan scribe:generate` يعيد التوليد بدون أخطاء.
- [ ] جميع روابط Scribe (لـ viewings و appointments) تعمل في الـ API.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
