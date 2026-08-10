# المهمة #12: إدارة العملاء المحتملين (Leads)

| الحقل | القيمة |
|---|---|
| **الحالة** | ✅ مكتمل |
| **الأولوية** | 🔴 عالية (جوهر الـ CRM) |
| **الجهد المقدّر** | 10–14 ساعة |
| **الاعتمادية** | يجب أن يسبقها #10 (لدور trader) و #11 (لـ polymorphic appointments) |
| **راجع** | `docs/ideas/mini-crm/report.md` (السيناريوهات 1، 4، 5؛ القواعد 1–7، 15–17) |

---

## الوضع الحالي

لا يوجد `Lead` أو ما يشابهه في النظام. كل قواعد CRM الـ 17 الواردة في التقرير (السطور 68–86) تحتاج entity جديد. هذه الخطة تبني الـ CRUD الأساسي + تدفّق الحالات + الأرشفة + البحث + الإحصائيات.

---

## مراحل التنفيذ

### المرحلة 1 — Migrations و Enums (1 ساعة) ✅

**الملفات:**
- `Modules/Crm/Enums/LeadStatus.php`:
  ```php
  <?php
  namespace Modules\Crm\Enums;

  enum LeadStatus: string
  {
      case NEW = 'new';
      case CONTACTED = 'contacted';
      case QUALIFIED = 'qualified';
      case WON = 'won';
      case LOST = 'lost';

      public function label(): string
      {
          return match ($this) {
              self::NEW => __('crm.status_new'),
              self::CONTACTED => __('crm.status_contacted'),
              self::QUALIFIED => __('crm.status_qualified'),
              self::WON => __('crm.status_won'),
              self::LOST => __('crm.status_lost'),
          };
      }
  }
  ```
- `Modules/Crm/Enums/LeadSource.php`:
  ```php
  <?php
  namespace Modules\Crm\Enums;

  enum LeadSource: string
  {
      case WEBSITE = 'website';
      case WHATSAPP = 'whatsapp';
      case REFERRAL = 'referral';
      case WALK_IN = 'walk_in';
      case PHONE = 'phone';
      case OTHER = 'other';
  }
  ```
- `Modules/Crm/Database/Migrations/2026_06_27_000001_create_leads_table.php`:
  ```php
  Schema::create('leads', function (Blueprint $table) {
      $table->id();
      $table->foreignId('trader_id')->constrained('users')->cascadeOnDelete();
      $table->string('name');
      $table->string('phone', 32)->index();
      $table->string('email')->nullable()->index();
      $table->string('source', 32);
      $table->string('status', 32)->default('new')->index();
      $table->text('lost_reason')->nullable();
      $table->timestamp('status_changed_at')->nullable();
      $table->timestamp('archived_at')->nullable()->index();
      $table->timestamp('last_activity_at')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['trader_id', 'status']);
  });
  ```

---

### المرحلة 2 — Entity و DTO (1.5 ساعة) ✅

**الملفات:**
- `Modules/Crm/Entities/Lead.php`:
  ```php
  <?php
  namespace Modules\Crm\Entities;

  use App\Models\BaseModel;
  use Illuminate\Database\Eloquent\Relations\BelongsTo;
  use Illuminate\Database\Eloquent\Relations\HasMany;
  use Illuminate\Database\Eloquent\Relations\MorphMany;
  use Illuminate\Database\Eloquent\SoftDeletes;
  use Modules\Auth\Entities\User;
  use Modules\Crm\Enums\LeadStatus;
  use Modules\Crm\Enums\LeadSource;
  use Modules\RealEstate\Entities\Appointment;

  class Lead extends BaseModel
  {
      use SoftDeletes;

      protected $fillable = [
          'trader_id', 'name', 'phone', 'email',
          'source', 'status', 'lost_reason',
          'status_changed_at', 'last_activity_at',
      ];

      protected $casts = [
          'source' => LeadSource::class,
          'status' => LeadStatus::class,
          'status_changed_at' => 'datetime',
          'archived_at' => 'datetime',
          'last_activity_at' => 'datetime',
      ];

      protected static $filterableColumns = ['status', 'source', 'trader_id'];
      protected static $searchableColumns = ['name', 'phone', 'email'];
      protected static $dateFilterableColumns = ['created_at', 'status_changed_at', 'last_activity_at'];

      public function trader(): BelongsTo
      {
          return $this->belongsTo(User::class, 'trader_id');
      }

      public function notes(): HasMany
      {
          return $this->hasMany(LeadNote::class);
      }

      public function followUps(): MorphMany
      {
          return $this->morphMany(Appointment::class, 'followable');
      }

      public function scopeActive($query)
      {
          return $query->whereNull('archived_at');
      }

      public function scopeArchived($query)
      {
          return $query->whereNotNull('archived_at');
      }

      public function scopeRecentlyActive($query)
      {
          return $query->orderByDesc('last_activity_at');
      }

      public function isOwnedBy(int $userId): bool
      {
          return $this->trader_id === $userId;
      }

      public function touchActivity(): void
      {
          $this->forceFill(['last_activity_at' => now()])->saveQuietly();
      }
  }
  ```
- `Modules/Crm/DTOs/LeadDTO.php`:
  ```php
  <?php
  namespace Modules\Crm\DTOs;

  use App\DTOs\DTOInterface;
  use Illuminate\Http\Request;

  readonly final class LeadDTO implements DTOInterface
  {
      public function __construct(
          public int $trader_id,
          public string $name,
          public string $phone,
          public ?string $email,
          public string $source,
      ) {}

      public static function fromRequest(array $data): self
      {
          return new self(
              trader_id: $data['trader_id'] ?? auth()->id(),
              name: $data['name'],
              phone: $data['phone'],
              email: $data['email'] ?? null,
              source: $data['source'],
          );
      }

      public function toArray(): array
      {
          return [
              'trader_id' => $this->trader_id,
              'name' => $this->name,
              'phone' => $this->phone,
              'email' => $this->email,
              'source' => $this->source,
          ];
      }
  }
  ```
- `Modules/Crm/DTOs/ChangeLeadStatusDTO.php` — مشابه بحقول `status` و `lost_reason`.

---

### المرحلة 3 — Service (3 ساعات) ✅

**الملف:** `Modules/Crm/Services/LeadService.php`:
```php
<?php
namespace Modules\Crm\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Crm\DTOs\ChangeLeadStatusDTO;
use Modules\Crm\DTOs\LeadDTO;
use Modules\Crm\Enums\LeadStatus;
use Modules\Crm\Entities\Lead;

class LeadService extends BaseService
{
    protected const CACHE_TAG = 'crm_leads';

    public function list(): LengthAwarePaginator
    {
        return Lead::query()
            ->where('trader_id', auth()->id())
            ->active()
            ->with(['notes', 'followUps'])
            ->filter()
            ->recentlyActive()
            ->paginate($this->getPerPage());
    }

    public function archived(): LengthAwarePaginator
    {
        return Lead::query()
            ->where('trader_id', auth()->id())
            ->archived()
            ->filter()
            ->orderByDesc('archived_at')
            ->paginate($this->getPerPage());
    }

    public function find(int $id): Lead
    {
        $lead = Lead::with(['notes', 'followUps', 'trader'])->findOrFail($id);
        abort_unless($lead->isOwnedBy(auth()->id()), 403, 'unauthorized_lead_access');
        return $lead;
    }

    public function store(LeadDTO $dto): Lead
    {
        $lead = DB::transaction(function () use ($dto) {
            $lead = Lead::create($dto->toArray() + [
                'status' => LeadStatus::NEW,
                'status_changed_at' => now(),
                'last_activity_at' => now(),
            ]);
            $lead->touchActivity();
            return $lead;
        });
        $this->clearCache();
        return $lead;
    }

    public function update(Lead $lead, LeadDTO $dto): Lead
    {
        abort_unless($lead->isOwnedBy(auth()->id()), 403);
        DB::transaction(function () use ($lead, $dto) {
            $lead->update($dto->toArray());
            $lead->touchActivity();
        });
        $this->clearCache();
        return $lead->fresh();
    }

    public function changeStatus(Lead $lead, ChangeLeadStatusDTO $dto): Lead
    {
        abort_unless($lead->isOwnedBy(auth()->id()), 403);
        if ($dto->status === LeadStatus::LOST && empty($dto->lost_reason)) {
            throw new \InvalidArgumentException('lost_reason_required');
        }
        return DB::transaction(function () use ($lead, $dto) {
            $lead->update([
                'status' => $dto->status,
                'lost_reason' => $dto->lost_reason,
                'status_changed_at' => now(),
            ]);
            $lead->touchActivity();
            $this->clearCache();
            return $lead->fresh();
        });
    }

    public function archive(Lead $lead): Lead
    {
        abort_unless($lead->isOwnedBy(auth()->id()), 403);
        $lead->update(['archived_at' => now(), 'last_activity_at' => now()]);
        $this->clearCache();
        return $lead;
    }

    public function restore(int $id): Lead
    {
        $lead = Lead::onlyTrashed()->findOrFail($id);
        abort_unless($lead->isOwnedBy(auth()->id()), 403);
        if ($lead->archived_at && $lead->archived_at->lt(now()->subDays(30))) {
            throw new \RuntimeException('archive_period_expired');
        }
        $lead->restore();
        $lead->update(['archived_at' => null, 'last_activity_at' => now()]);
        $this->clearCache();
        return $lead;
    }

    public function checkDuplicatePhone(string $phone): bool
    {
        return Lead::where('trader_id', auth()->id())
            ->where('phone', $phone)
            ->exists();
    }
}
```

---

### المرحلة 4 — FormRequests (1 ساعة) ✅

**الملفات:**
- `Modules/Crm/Http/Requests/StoreLeadRequest.php`:
  ```php
  public function rules(): array
  {
      return [
          'name' => ['required', 'string', 'max:120'],
          'phone' => ['required', 'string', 'max:32'],
          'email' => ['nullable', 'email', 'max:120'],
          'source' => ['required', 'string', 'in:'.implode(',', array_column(LeadSource::cases(), 'value'))],
      ];
  }
  ```
- `Modules/Crm/Http/Requests/UpdateLeadRequest.php` — نفس القواعد مع `sometimes`.
- `Modules/Crm/Http/Requests/ChangeLeadStatusRequest.php`:
  ```php
  public function rules(): array
  {
      return [
          'status' => ['required', 'string', 'in:'.implode(',', array_column(LeadStatus::cases(), 'value'))],
          'lost_reason' => ['required_if:status,lost', 'nullable', 'string', 'max:500'],
      ];
  }
  ```

---

### المرحلة 5 — Resource و Policy و Controller (2.5 ساعة) ✅

**الملفات:**
- `Modules/Crm/Http/Resources/LeadResource.php`:
  ```php
  <?php
  namespace Modules\Crm\Http\Resources;

  use App\Http\Resources\BaseJsonResource;

  class LeadResource extends BaseJsonResource
  {
      public function getCustomData(): array
      {
          return [
              'id' => $this->id,
              'name' => $this->name,
              'phone' => $this->phone,
              'email' => $this->email,
              'source' => $this->source?->value,
              'status' => $this->status?->value,
              'status_label' => $this->status?->label(),
              'lost_reason' => $this->lost_reason,
              'status_changed_at' => $this->status_changed_at?->format('Y-m-d H:i:s'),
              'archived_at' => $this->archived_at?->format('Y-m-d H:i:s'),
              'last_activity_at' => $this->last_activity_at?->format('Y-m-d H:i:s'),
              'created_at' => $this->created_at->format('Y-m-d H:i:s'),
          ];
      }

      public function getRelationMap(): array
      {
          return [
              'notes' => LeadNoteResource::class,
              'followUps' => \Modules\RealEstate\Http\Resources\AppointmentResource::class,
              'trader' => \Modules\Auth\Http\Resources\UserResource::class,
          ];
      }
  }
  ```
- `Modules/Crm/Policies/LeadPolicy.php`:
  ```php
  public function view(User $user, Lead $lead): bool
  {
      return $lead->isOwnedBy($user->id);
  }
  public function update(User $user, Lead $lead): bool
  {
      return $lead->isOwnedBy($user->id);
  }
  public function delete(User $user, Lead $lead): bool
  {
      return $lead->isOwnedBy($user->id) && $lead->notes()->count() === 0 && $lead->followUps()->count() === 0;
  }
  ```
  (ملاحظة: التقرير يسمح بالأرشفة بدل الحذف — هذه السياسة لـ `forceDelete` فقط، والحذف العادي محظور أصلًا في الـ Controller.)
- `Modules/Crm/Http/Controllers/LeadController.php` — مع `ApplyPermissions` trait، يستدعي `LeadService` ويستخدم `ApiResponses`.

---

### المرحلة 6 — Routes (1 ساعة) ✅

**الملف:** `Modules/Crm/Routes/api.php`:
```php
Route::prefix('api/dashboard/crm')->middleware(['auth:sanctum', 'trader'])->group(function () {
    Route::get('leads', [LeadController::class, 'index'])->name('api.crm.leads.index');
    Route::post('leads', [LeadController::class, 'store'])->name('api.crm.leads.store');
    Route::get('leads/archived', [LeadController::class, 'archived'])->name('api.crm.leads.archived');
    Route::get('leads/{id}', [LeadController::class, 'show'])->name('api.crm.leads.show');
    Route::patch('leads/{id}', [LeadController::class, 'update'])->name('api.crm.leads.update');
    Route::patch('leads/{id}/status', [LeadController::class, 'changeStatus'])->name('api.crm.leads.status');
    Route::post('leads/{id}/archive', [LeadController::class, 'archive'])->name('api.crm.leads.archive');
    Route::post('leads/{id}/restore', [LeadController::class, 'restore'])->name('api.crm.leads.restore');
    Route::get('leads/check-duplicate', [LeadController::class, 'checkDuplicate'])->name('api.crm.leads.check-duplicate');
});
```

---

### المرحلة 7 — الاختبارات (2 ساعة) ✅
**الملف:** `Modules/Crm/Tests/LeadTest.php` يغطي:
1. إنشاء lead بحقول صحيحة → 201.
2. إنشاء lead بـ phone مكرر (نفس التاجر) → 201 (مع تحذير) — يُختبر endpoint `check-duplicate` منفصلًا.
3. إنشاء lead بحقول ناقصة → 422.
4. قائمة leads مع pagination و search و status filter.
5. تغيير الحالة لغير `lost` بدون `lost_reason` → 200.
6. تغيير الحالة إلى `lost` بدون `lost_reason` → 422.
7. الأرشفة → ينتقل من active إلى archived.
8. استرجاع من الأرشيف (أقل من 30 يوم) → 200.
9. استرجاع من الأرشيف (أكثر من 30 يوم) → 422.
10. تاجر آخر يحاول الوصول لـ lead تاجر أول → 403.
11. `last_activity_at` يُحدّث عند أي تعديل.

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Migrations) + 2 (Entity/DTO) | الـ entity يعتمد على الـ migration فقط لـ table name | لا شيء |
| 3 (Service) | يُكتب بالتوازي مع 4 و 5 | يعتمد على 2 |
| 4 (Requests) | لا تتقاطع مع 5 | يعتمد على 1 (للقيم الـ enum) |
| 5 (Resource/Policy/Controller) | الـ Controller يستهلك 3 و 4 | يعتمد على 3 و 4 |
| 6 (Routes) | يُكتب بعد 5 | يعتمد على 5 |
| 7 (Tests) | بعد 6 | يعتمد على 5 و 6 |

---

## معايير القبول

- [x] `php artisan migrate` ينشئ جدول `leads` بكل الأعمدة والـ indexes.
- [x] `php artisan test --testsuite=Modules --filter=LeadTest` ينجح بكل السيناريوهات الـ 11.
- [x] تاجر `A` يستطيع إنشاء lead، وتاجر `B` لا يستطيع رؤيته.
- [x] تغيير الحالة إلى `lost` يطلب `lost_reason` إلزاميًا.
- [x] الأرشفة تخفي الـ lead من القوائم النشطة.
- [x] استرجاع الأرشيف يعمل خلال 30 يومًا فقط.
- [x] البحث بـ `?search=أحمد` يعيد نتائج مطابقة للاسم/الهاتف/البريد.
- [x] تصفية بـ `?status=new` و `?source=whatsapp` تعمل.
- [x] `last_activity_at` يُحدّث عند أي تعديل على الـ lead.
- [x] endpoint `GET /leads/check-duplicate?phone=...` يعيد `{"duplicate": true|false}`.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
