# المهمة #17: سطح API لبطاقات التأجير (Controller + Requests + Resources + Policy + Routes)

> **التقرير المصدر:** `docs/ideas/rental-cards/report.md` (السيناريوهات 1–6، القواعد 6, 17–19, 28–31)
> **الهدف:** كشف كل عمليات بطاقات التأجير عبر REST API (dashboard): عرض/إنشاء/تعديل/إنهاء/تجديد/حذف/تاريخ/البطاقة النشطة لعقار معيّن.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 عالية
> **الجهد المقدّر:** 1.5 – 2 يوم
> **الاعتمادية:** [#15](./15-rental-cards-data-foundation.md) + [#16](./16-rental-cards-service-and-status-integration.md)
> **راجع:** `report.md` السطور 26–82 (السيناريوهات 1–6), 96 (القاعدة 6 — المالك فقط), 112–115 (المستأجر الخارجي), 129–133 (تعديل/حذف), 176–198 (معايير القبول 1–8, 13–19, 21).

---

## الوضع الحالي

لا توجد نقاط نهاية (endpoints) خاصة ببطاقات التأجير. المتحكم الحالي `Modules/RealEstate/Http/Controllers/PropertyController.php` لا يتعامل مع التأجير. هذه الخطة تضيف:

- `RentalCardController` (8 actions)
- 4 FormRequests للتحقق
- 2 Resources (للعرض المفرد والعرض الجماعي)
- `RentalCardPolicy` (لـ authorization)
- تسجيل الـ routes والـ policy

## المرحلة 1: FormRequests (~ 2-3 ساعات)

### 1.1 `CreateRentalCardRequest`

`Modules/RealEstate/Http/Requests/CreateRentalCardRequest.php`:

```php
<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;

class CreateRentalCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\RealEstate\Entities\RentalCard::class);
    }

    public function rules(): array
    {
        return [
            'property_id' => [
                'required', 'integer',
                Rule::exists('properties', 'id')->where(fn ($q) =>
                    $q->where('publisher_id', $this->user()->id)
                      ->where('status', PropertyStatus::APPROVED->value)
                ),
            ],
            'tenant_user_id' => ['nullable', 'integer', Rule::exists((new User)->getTable(), 'id')],
            'external_tenant_name' => ['nullable', 'string', 'max:255', 'required_without:tenant_user_id'],
            'external_tenant_phone' => ['nullable', 'string', 'max:32'],
            'external_tenant_email' => ['nullable', 'email', 'max:255'],
            'external_tenant_id_notes' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_renewable' => ['nullable', 'boolean'],
            'pre_rental_photo_ids' => ['nullable', 'array', 'max:20'],
            'pre_rental_photo_ids.*' => ['integer'], // يتحقق من الانتماء لنظام الملفات في الخدمة
        ];
    }
}
```

**ملاحظات:**
- `property_id` يجب أن يكون عقارًا يملكه المستخدم الحالي وحالته `APPROVED` (مما يضمن القاعدة 7 و 8 من التقرير).
- `external_tenant_name` إلزامي فقط إذا `tenant_user_id` غير موجود (`required_without`) — معيار القبول 4.
- `pre_rental_photo_ids` يُترك للتحقق منه في الخدمة (تعتمد على نظام الملفات) — خطة #18.

### 1.2 `UpdateRentalCardRequest`

نفس قواعد `CreateRentalCardRequest` **بدون** الحقول المحمية:
- غير مسموح: `property_id`, `owner_id`, `tenant_user_id`, `start_date`
- مسموح: `end_date` (للتجديد), `terms`, `notes`, `is_renewable`

### 1.3 `EndRentalCardRequest`

```php
return [
    'ended_at' => ['nullable', 'date', 'before_or_equal:today'],
    'end_reason' => ['nullable', 'string', 'max:1000'],
];
```

### 1.4 `RenewRentalCardRequest`

```php
return [
    'start_date' => ['nullable', 'date', 'after_or_equal:today'],
    'end_date' => ['required', 'date', 'after:start_date'],
    'terms' => ['nullable', 'string', 'max:5000'],
    'notes' => ['nullable', 'string', 'max:5000'],
    'is_renewable' => ['nullable', 'boolean'],
];
```

### 1.5 `RentalCardFilterRequest`

```php
return [
    'property_id' => ['nullable', 'integer'],
    'status' => ['nullable', 'string', Rule::in(\Modules\RealEstate\Enums\RentalCardStatus::values())],
    'is_renewable' => ['nullable', 'boolean'],
    'search' => ['nullable', 'string', 'max:100'],
    'start_date' => ['nullable', 'date'],
    'end_date' => ['nullable', 'date'],
];
```

## المرحلة 2: Resources (~ 2 ساعات)

`Modules/RealEstate/Http/Resources/RentalCardResource.php`:

```php
<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class RentalCardResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'property' => PropertyResource::class,
            'owner' => \Modules\Auth\Http\Resources\UserResource::class,
            'tenantUser' => \Modules\Auth\Http\Resources\UserResource::class,
            'endedBy' => \Modules\Auth\Http\Resources\UserResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'owner_id' => $this->owner_id,
            'tenant' => [
                'type' => $this->is_external_tenant ? 'external' : 'registered',
                'user_id' => $this->tenant_user_id,
                'name' => $this->tenant_display_name,
                'phone' => $this->tenant_user_id
                    ? null
                    : $this->external_tenant_phone,
                'email' => $this->tenant_user_id
                    ? null
                    : $this->external_tenant_email,
                'id_notes' => $this->tenant_user_id
                    ? null
                    : $this->external_tenant_id_notes,
            ],
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'days_remaining' => $this->days_remaining,
            'terms' => $this->terms,
            'notes' => $this->notes,
            'is_renewable' => (bool) $this->is_renewable,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'is_active' => $this->status?->value === 'active',
            'renewal_count' => (int) $this->renewal_count,
            'ended_at' => $this->ended_at?->format('Y-m-d H:i:s'),
            'end_reason' => $this->end_reason,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
```

`Modules/RealEstate/Http/Resources/RentalCardCollection.php` — يرث من `BaseJsonResource` ويُرجع collection (الـ pagination يعتني بالباقي).

## المرحلة 3: Policy (~ 1 ساعة)

`Modules/RealEstate/Policies/RentalCardPolicy.php`:

```php
<?php

namespace Modules\RealEstate\Policies;

use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\RentalCard;

class RentalCardPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('list', RentalCard::class)
            || $user->hasPermissionTo('rental_cards.list');
    }

    public function view(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            || $user->hasPermissionTo('rental_cards.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rental_cards.create');
    }

    public function update(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            && $user->hasPermissionTo('rental_cards.edit');
    }

    public function delete(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            && $user->hasPermissionTo('rental_cards.delete');
    }

    public function end(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            && $user->hasPermissionTo('rental_cards.end');
    }

    public function renew(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            && $user->hasPermissionTo('rental_cards.renew');
    }
}
```

تسجيل الـ policy في `Modules/RealEstate/Providers/RealEstateServiceProvider.php`:

```php
$this->registerPolicies([
    Property::class => PropertyPolicy::class,
    AdGroup::class => AdGroupPolicy::class,
    Ad::class => AdPolicy::class,
    RentalCard::class => RentalCardPolicy::class, // ← جديد
]);
```

## المرحلة 4: Controller (~ 3-4 ساعات)

`Modules/RealEstate/Http/Controllers/RentalCardController.php`:

```php
<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\RealEstate\DTOs\CreateRentalCardDTO;
use Modules\RealEstate\DTOs\EndRentalCardDTO;
use Modules\RealEstate\DTOs\RenewRentalCardDTO;
use Modules\RealEstate\DTOs\UpdateRentalCardDTO;
use Modules\RealEstate\Http\Requests\CreateRentalCardRequest;
use Modules\RealEstate\Http\Requests\EndRentalCardRequest;
use Modules\RealEstate\Http\Requests\RenewRentalCardRequest;
use Modules\RealEstate\Http\Requests\RentalCardFilterRequest;
use Modules\RealEstate\Http\Requests\UpdateRentalCardRequest;
use Modules\RealEstate\Http\Resources\RentalCardResource;
use Modules\RealEstate\Services\RentalCardService;

class RentalCardController extends Controller
{
    use ApiResponses, ApplyPermissions;

    public function __construct(
        protected readonly RentalCardService $service
    ) {
        $this->applyPermissions(
            'rental_cards',
            ['index', 'show', 'store', 'update', 'destroy'],
            [
                'active' => 'show',
                'history' => 'list',
                'end' => 'end',
                'renew' => 'renew',
            ]
        );
    }

    public function index(RentalCardFilterRequest $request): JsonResponse
    {
        $cards = $this->service->list(
            propertyId: $request->integer('property_id'),
            ownerId: $request->integer('owner_id') ?? Auth::id(),
        );

        return $this->paginatedResponse(RentalCardResource::collection($cards));
    }

    public function show(int $id): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('view', $card)) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(RentalCardResource::make($card));
    }

    public function active(int $propertyId): JsonResponse
    {
        $card = $this->service->activeForProperty($propertyId);

        if (! $card) {
            return $this->successResponse(null, __('messages.no_active_rental'));
        }

        if (Gate::denies('view', $card)) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(RentalCardResource::make($card));
    }

    public function history(int $propertyId, RentalCardFilterRequest $request): JsonResponse
    {
        $cards = $this->service->history($propertyId);

        return $this->paginatedResponse(RentalCardResource::collection($cards));
    }

    public function store(CreateRentalCardRequest $request): JsonResponse
    {
        $dto = CreateRentalCardDTO::fromRequest(array_merge(
            $request->validated(),
            ['owner_id' => Auth::id()]
        ));

        $card = $this->service->create($dto);

        return $this->successResponse(
            RentalCardResource::make($card),
            __('messages.rental_card_created')
        )->created('rental_card');
    }

    public function update(int $id, UpdateRentalCardRequest $request): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('update', $card)) {
            return $this->forbiddenResponse();
        }

        $dto = UpdateRentalCardDTO::fromRequest($request->validated());
        $card = $this->service->update($id, $dto);

        return $this->successResponse(
            RentalCardResource::make($card),
            __('messages.rental_card_updated')
        );
    }

    public function end(int $id, EndRentalCardRequest $request): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('end', $card)) {
            return $this->forbiddenResponse();
        }

        $dto = EndRentalCardDTO::fromRequest($request->validated());
        $card = $this->service->end($id, $dto);

        return $this->successResponse(
            RentalCardResource::make($card),
            __('messages.rental_card_ended')
        );
    }

    public function renew(int $id, RenewRentalCardRequest $request): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('renew', $card)) {
            return $this->forbiddenResponse();
        }

        $dto = RenewRentalCardDTO::fromRequest($request->validated());
        $card = $this->service->renew($id, $dto);

        return $this->successResponse(
            RentalCardResource::make($card),
            __('messages.rental_card_renewed')
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('delete', $card)) {
            return $this->forbiddenResponse();
        }

        $this->service->delete($id);

        return $this->successResponse()->deleted('rental_card');
    }
}
```

## المرحلة 5: Routes (~ 30 دقيقة)

`Modules/RealEstate/Routes/api.php` — إضافة داخل مجموعة `Route::prefix('api/dashboard')->middleware(['auth:sanctum'])`:

```php
// Rental Cards
Route::get('properties/{propertyId}/rental-cards/active', [RentalCardController::class, 'active'])
    ->name('api.dashboard.rental-cards.active');
Route::get('properties/{propertyId}/rental-cards/history', [RentalCardController::class, 'history'])
    ->name('api.dashboard.rental-cards.history');

Route::get('rental-cards', [RentalCardController::class, 'index'])
    ->name('api.dashboard.rental-cards.index');
Route::post('rental-cards', [RentalCardController::class, 'store'])
    ->name('api.dashboard.rental-cards.store');
Route::get('rental-cards/{id}', [RentalCardController::class, 'show'])
    ->name('api.dashboard.rental-cards.show');
Route::patch('rental-cards/{id}', [RentalCardController::class, 'update'])
    ->name('api.dashboard.rental-cards.update');
Route::delete('rental-cards/{id}', [RentalCardController::class, 'destroy'])
    ->name('api.dashboard.rental-cards.destroy');
Route::patch('rental-cards/{id}/end', [RentalCardController::class, 'end'])
    ->name('api.dashboard.rental-cards.end');
Route::patch('rental-cards/{id}/renew', [RentalCardController::class, 'renew'])
    ->name('api.dashboard.rental-cards.renew');
```

وإضافة الـ import في أعلى الملف:

```php
use Modules\RealEstate\Http\Controllers\RentalCardController;
```

## المرحلة 6: رسائل i18n إضافية (~ 15 دقيقة)

`lang/ar/messages.php`:

```php
'rental_card_created' => 'تم إنشاء بطاقة التأجير بنجاح.',
'rental_card_updated' => 'تم تحديث البطاقة بنجاح.',
'rental_card_ended' => 'تم إنهاء بطاقة التأجير.',
'rental_card_renewed' => 'تم تجديد بطاقة التأجير.',
'no_active_rental' => 'لا توجد بطاقة تأجير نشطة على هذا العقار.',
```

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1 (FormRequests) + المرحلة 2 (Resources) | لا تعارض في الملفات | #15 (DTO types) |
| المرحلة 3 (Policy) + المرحلة 4 (Controller) | الـ Policy مستقل، الـ Controller يعتمد عليها فقط في `Gate::denies` | #15 (نموذج RentalCard) |
| المرحلة 5 (Routes) + المرحلة 6 (i18n) | لا تعارض | #15, #16, المراحل 1-4 |

> **ملاحظة:** هذه الخطة تعتمد كليًا على #15 و #16 ولا يمكن أن تعمل بالتوازي معها. كما أنها قابلة للتشغيل بالتوازي مع #18 (صور قبل التأجير) إذا أُريد ذلك — أي تعديل على `CreateRentalCardRequest` و `RentalCardResource` ليدعم `pre_rental_photo_ids` يكون اختياريًا ولا يكسر الخطة الحالية.

## معايير القبول

- [ ] `POST /api/dashboard/rental-cards` ينشئ بطاقة جديدة (مع tenant مسجّل أو خارجي) ويُرجع 201.
- [ ] `POST /api/dashboard/rental-cards` يرفض (`422`) عند: عقار غير مملوك للمستخدم، عقار بحالة غير APPROVED، تاريخ نهاية قبل بداية، مستأجر خارجي بدون اسم.
- [ ] `GET /api/dashboard/rental-cards` يعرض قائمة مع pagination و filters (property_id, status, is_renewable, search, dates).
- [ ] `GET /api/dashboard/rental-cards/{id}` يرجع 200 لمالك البطاقة، 403 لمستخدم آخر.
- [ ] `GET /api/dashboard/properties/{propertyId}/rental-cards/active` يرجع البطاقة النشطة أو null.
- [ ] `GET /api/dashboard/properties/{propertyId}/rental-cards/history` يرجع كل البطاقات السابقة بترتيب زمني.
- [ ] `PATCH /api/dashboard/rental-cards/{id}` يعدّل `terms`, `notes`, `is_renewable` فقط (لا `property_id`, `tenant_user_id`, `start_date`).
- [ ] `PATCH /api/dashboard/rental-cards/{id}/end` ينهي البطاقة، ويرجع 422 إذا غير نشطة.
- [ ] `PATCH /api/dashboard/rental-cards/{id}/renew` يجدد فقط إذا `is_renewable = true`، ويرفع `renewal_count`.
- [ ] `DELETE /api/dashboard/rental-cards/{id}` يرجع 422 إذا البطاقة نشطة.
- [ ] `RentalCardPolicy` يفرض الملكية الفردية (المالك فقط) ويستثني `super-admin`.
- [ ] `RentalCardResource` يُرجع بنية tenant موحّدة (type, user_id, name, phone, email, id_notes).
- [ ] كل المسارات مسجلة في `Modules/RealEstate/Routes/api.php` ضمن مجموعة `auth:sanctum`.
- [ ] الـ Policy مسجلة في `RealEstateServiceProvider`.
- [ ] كل رسائل الخطأ والنجاح موجودة في `lang/ar/messages.php`.
- [ ] `composer pint` يمر بدون أخطاء.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
