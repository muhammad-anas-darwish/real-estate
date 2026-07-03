# المهمة #15: تأسيس بيانات بطاقات التأجير (Rental Cards — Data Foundation)

> **التقرير المصدر:** `docs/ideas/rental-cards/report.md` (القسم: القواعد والمنطق 1–31، معايير القبول 1–23)
> **الهدف:** إنشاء الطبقة الأساسية (Migration + Model + Enum + DTOs + Permissions) لميزة بطاقات التأجير — دون أي منطق خدمة أو API حتى الآن.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🔴 عالية
> **الجهد المقدّر:** 0.5 – 1 يوم
> **الاعتمادية:** لا شيء (هذه الخطة هي الأساس الذي تبني عليه الخطط #16 → #19)
> **راجع:** `report.md` السطور 88–133 (القواعد) و175–198 (معايير القبول)

---

## الوضع الحالي

لا يوجد في النظام اليوم أي مفهوم لـ "بطاقة تأجير". العقار (`Property`) يملك حالات إدارية فقط (`PENDING, UNDER_INSPECTION, APPROVED, REJECTED, SUSPENDED, SOLD, ARCHIVED, DRAFT` في `Modules\RealEstate\Enums\PropertyStatus.php:5-14`) ولا يوجد جدول يربط العقار بمستأجر. هذه الخطة تضيف البنية التحتية لجدول `rental_cards` مع كل الحقول اللازمة، لكنها لا تُنفّذ أي سلوك بعد (لا خدمة، لا متحكم، لا اختبارات قبول) — هذا متروك للخطط اللاحقة.

## المرحلة 1: Migration + Enum (~ 2-3 ساعات)

**الهدف:** إنشاء جدول `rental_cards` مع جميع الأعمدة اللازمة، وإضافة enum لحالة البطاقة.

### 1.1 إنشاء Enum لحالة البطاقة

`Modules/RealEstate/Enums/RentalCardStatus.php`:

```php
<?php

namespace Modules\RealEstate\Enums;

enum RentalCardStatus: string
{
    case ACTIVE = 'active';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';
    case RENEWED = 'renewed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::ENDED => 'Ended',
            self::CANCELLED => 'Cancelled',
            self::RENEWED => 'Renewed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::ENDED, self::CANCELLED, self::RENEWED], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### 1.2 Migration لجدول `rental_cards`

`Modules/RealEstate/Database/Migrations/2026_06_27_000001_create_rental_cards_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_cards', function (Blueprint $table) {
            $table->id();

            // الربط بالعقار
            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();

            // مالك البطاقة (منشئها) — للأمان والـ ownership
            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // المستأجر — نوعان: مسجّل (user_id) أو خارجي (external_*)
            $table->foreignId('tenant_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // بيانات المستأجر الخارجي (تُملأ فقط عندما tenant_user_id = null)
            $table->string('external_tenant_name')->nullable();
            $table->string('external_tenant_phone', 32)->nullable();
            $table->string('external_tenant_email')->nullable();
            $table->text('external_tenant_id_notes')->nullable();

            // مدة التأجير
            $table->date('start_date');
            $table->date('end_date');

            // محتوى البطاقة
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_renewable')->default(false);

            // الحالة
            $table->string('status')->default('active');

            // إنهاء مبكر
            $table->timestamp('ended_at')->nullable();
            $table->text('end_reason')->nullable();
            $table->foreignId('ended_by')->nullable()
                ->constrained('users')->nullOnDelete();

            // تجديد
            $table->timestamp('renewed_at')->nullable();
            $table->unsignedInteger('renewal_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // فهارس لتحسين أداء الاستعلامات الشائعة
            $table->index(['property_id', 'status']);
            $table->index(['owner_id', 'status']);
            $table->index(['tenant_user_id']);
            $table->index(['end_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_cards');
    }
};
```

**ملاحظات معمارية:**
- `owner_id` و `tenant_user_id` كلاهما مفصولان. عندما يكون `tenant_user_id` غير فارغ → مستأجر مسجّل. عندما يكون فارغًا → مستأجر خارجي (تُملأ حقول `external_tenant_*`).
- `cascadeOnDelete` على `property_id` يضمن حذف البطاقات عند حذف العقار (معيار القبول #22).
- `nullOnDelete` على `tenant_user_id` لأن حذف حساب المستأجر لا يجب أن يحذف البطاقة (القاعدة من السيناريو 8: "بيانات البطاقة تبقى كما هي").

### 1.3 قيد فحص (database-level check) اختياري للمستأجر الخارجي

لا حاجة لـ CHECK constraint في MySQL (غير مدعوم بشكل موحّد). التحقق سيُجرى في الـ FormRequest (خطة #17) والـ Service (خطة #16).

## المرحلة 2: Model + DTOs + Permissions (~ 3-4 ساعات)

### 2.1 Model

`Modules/RealEstate/Entities/RentalCard.php`:

```php
<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Enums\RentalCardStatus;

class RentalCard extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'rental_cards';

    protected $fillable = [
        'property_id',
        'owner_id',
        'tenant_user_id',
        'external_tenant_name',
        'external_tenant_phone',
        'external_tenant_email',
        'external_tenant_id_notes',
        'start_date',
        'end_date',
        'terms',
        'notes',
        'is_renewable',
        'status',
        'ended_at',
        'end_reason',
        'ended_by',
        'renewed_at',
        'renewal_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_renewable' => 'boolean',
        'status' => RentalCardStatus::class,
        'ended_at' => 'datetime',
        'renewed_at' => 'datetime',
        'renewal_count' => 'integer',
    ];

    protected static $filterableColumns = [
        'property_id',
        'owner_id',
        'tenant_user_id',
        'status',
        'is_renewable',
    ];

    protected static $multiFilterableColumns = ['id', 'property_id'];

    protected static $searchableColumns = [
        'external_tenant_name',
        'external_tenant_phone',
        'external_tenant_email',
        'notes',
    ];

    protected static $dateFilterableColumns = [
        'start_date',
        'end_date',
        'created_at',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', RentalCardStatus::ACTIVE);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function getIsExternalTenantAttribute(): bool
    {
        return $this->tenant_user_id === null;
    }

    public function getTenantDisplayNameAttribute(): string
    {
        return $this->tenant_user_id
            ? $this->tenantUser?->name ?? '—'
            : $this->external_tenant_name ?? '—';
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status !== RentalCardStatus::ACTIVE) {
            return 0;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->end_date, false));
    }

    protected static function newFactory()
    {
        return \Modules\RealEstate\Database\Factories\RentalCardFactory::new();
    }
}
```

### 2.2 Factory للاختبارات

`Modules/RealEstate/Database/Factories/RentalCardFactory.php`:

```php
<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\RentalCardStatus;

class RentalCardFactory extends Factory
{
    protected $model = RentalCard::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');
        $end = (new \DateTime($start->format('Y-m-d')))
            ->modify('+'.fake()->numberBetween(1, 12).' months');

        return [
            'property_id' => Property::factory(),
            'owner_id' => User::factory(),
            'tenant_user_id' => User::factory(),
            'external_tenant_name' => null,
            'external_tenant_phone' => null,
            'external_tenant_email' => null,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'terms' => fake()->optional(0.7)->paragraph(),
            'notes' => fake()->optional(0.5)->sentence(),
            'is_renewable' => fake()->boolean(30),
            'status' => RentalCardStatus::ACTIVE,
        ];
    }

    public function external(): static
    {
        return $this->state(fn () => [
            'tenant_user_id' => null,
            'external_tenant_name' => fake()->name(),
            'external_tenant_phone' => fake()->phoneNumber(),
            'external_tenant_email' => fake()->safeEmail(),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'status' => RentalCardStatus::ENDED,
            'ended_at' => now(),
        ]);
    }
}
```

### 2.3 DTOs

`Modules/RealEstate/DTOs/CreateRentalCardDTO.php`:

```php
<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;
use Illuminate\Http\Request;

readonly final class CreateRentalCardDTO implements DTOInterface
{
    public function __construct(
        public int $property_id,
        public int $owner_id,
        public ?int $tenant_user_id,
        public ?string $external_tenant_name,
        public ?string $external_tenant_phone,
        public ?string $external_tenant_email,
        public ?string $external_tenant_id_notes,
        public string $start_date,
        public string $end_date,
        public ?string $terms,
        public ?string $notes,
        public bool $is_renewable = false,
        public array $pre_rental_photo_ids = [],
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            property_id: (int) $data['property_id'],
            owner_id: (int) $data['owner_id'],
            tenant_user_id: isset($data['tenant_user_id']) ? (int) $data['tenant_user_id'] : null,
            external_tenant_name: $data['external_tenant_name'] ?? null,
            external_tenant_phone: $data['external_tenant_phone'] ?? null,
            external_tenant_email: $data['external_tenant_email'] ?? null,
            external_tenant_id_notes: $data['external_tenant_id_notes'] ?? null,
            start_date: $data['start_date'],
            end_date: $data['end_date'],
            terms: $data['terms'] ?? null,
            notes: $data['notes'] ?? null,
            is_renewable: (bool) ($data['is_renewable'] ?? false),
            pre_rental_photo_ids: $data['pre_rental_photo_ids'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'property_id' => $this->property_id,
            'owner_id' => $this->owner_id,
            'tenant_user_id' => $this->tenant_user_id,
            'external_tenant_name' => $this->external_tenant_name,
            'external_tenant_phone' => $this->external_tenant_phone,
            'external_tenant_email' => $this->external_tenant_email,
            'external_tenant_id_notes' => $this->external_tenant_id_notes,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'terms' => $this->terms,
            'notes' => $this->notes,
            'is_renewable' => $this->is_renewable,
        ];
    }
}
```

`Modules/RealEstate/DTOs/UpdateRentalCardDTO.php`, `Modules/RealEstate/DTOs/EndRentalCardDTO.php`, `Modules/RealEstate/DTOs/RenewRentalCardDTO.php` — تتبع نفس النمط، حقول قابلة للتعديل فقط (ممنوع تعديل `property_id`, `owner_id`, `tenant_user_id`, `start_date`).

### 2.4 إضافة الصلاحيات (Permissions)

تحديث `database/seeders/PermissionSeeder.php:11-35` — إضافة `rental_cards` كجموعة جديدة:

```php
'rental_cards' => ['list', 'show', 'create', 'edit', 'delete', 'end', 'renew'],
```

**معانيها:**
- `list` / `show` — عرض البطاقات
- `create` / `edit` / `delete` — CRUD
- `end` — إنهاء مبكر
- `renew` — تجديد

## المرحلة 3: التحقق (~ 1 ساعة)

1. `php artisan migrate` — يجب أن ينشئ الجدول بدون أخطاء.
2. `php artisan db:seed --class=PermissionSeeder` — يجب أن ينشئ الصلاحيات.
3. `composer pint` — تنسيق الكود.
4. `php artisan test --filter=RentalCardFactoryTest` (اختبار سريع للتحقق من الـ factory).

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1.1 + 1.2 + 1.3 | كلها داخل نفس الـ feature branch، ولا يوجد تعارض في الملفات | لا شيء |
| المرحلة 2 (Model + DTOs + Factory + Permissions) | كل ملف مستقل، يمكن بناءها بالتوازي | المرحلة 1 (الـ enum يجب أن يكون موجودًا قبل الـ casts في الـ model) |

> **ملاحظة:** لا توجد خطّتان أخريان في المشروع تستطيع هذه الخطة أن تعمل بالتوازي معها — كل الخطط اللاحقة (#16 → #19) تعتمد على هذه.

## معايير القبول

- [ ] `php artisan migrate` ينشئ جدول `rental_cards` بنجاح.
- [ ] جدول `rental_cards` يحوي كل الأعمدة المذكورة في `1.2`.
- [ ] فهارس `['property_id', 'status']` و `['owner_id', 'status']` و `['end_date', 'status']` موجودة.
- [ ] `Modules\RealEstate\Enums\RentalCardStatus` يحتوي على 4 حالات (ACTIVE, ENDED, CANCELLED, RENEWED) ودالة `isTerminal()`.
- [ ] `Modules\RealEstate\Entities\RentalCard` يرث من `BaseModel` ويعرّف `$filterableColumns`, `$searchableColumns`, `$dateFilterableColumns` كما في النمط الموجود.
- [ ] `RentalCard::scopeActive()` و `RentalCard::scopeForProperty()` و `RentalCard::scopeForOwner()` تعمل وتُرجع query builder صحيح.
- [ ] `RentalCard::getIsExternalTenantAttribute()` يُرجع `true` عندما `tenant_user_id` فارغ.
- [ ] `RentalCard::getDaysRemainingAttribute()` يُرجع عدد الأيام الصحيح للبطاقات النشطة و `0` للمنتهية.
- [ ] `RentalCardFactory::external()` ينشئ بطاقة بمستأجر خارجي.
- [ ] `CreateRentalCardDTO::fromRequest()` يُحوّل البيانات بنجاح (مع أو بدون `tenant_user_id`).
- [ ] `PermissionSeeder` يحوي مجموعة `rental_cards` بالصلاحيات السبع.
- [ ] `composer pint` يمر بدون أخطاء تنسيق.
- [ ] لا تغيير على `app/`, `database/migrations/*` (ما عدا ملف الـ migration الجديد), `Modules/Auth/`, `Modules/Core/`, `Modules/Communication/`.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
