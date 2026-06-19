# Implementation Plans — Real Estate Platform

> اختر الخطة التي تريد تنفيذها وقل لي "نفذ Plan X" وسأقوم ببنائها كاملة.

---

## Plan 1: Service Providers Module (مقدمو الخدمات)

### الوصف
إنشاء موديول جديد `ServiceProvider` يمكّن المحامين والمصورين والمفتشين والمسوقين من التسجيل كـ "مقدم خدمة" في المنصة، مع إدارة تخصصاتهم ومناطق تغطيتهم وأسعارهم.

### المهام

**1.1 تمديد User لدعم مقدمي الخدمات**
- إضافة حقل `user_type` (enum: `publisher`, `service_provider`) إلى users — منفصل عن `publisher_type` حتى يكون المستخدم Publisher + Service Provider معا إذا أراد.
- إضافة `ServiceProviderType` enum: `photographer`, `lawyer`, `inspector`, `marketer`, `other`.
- إضافة حقل `service_provider_type` + `is_service_provider` boolean.
- إضافة علاقة `serviceProviderProfile` على User.

**1.2 إنشاء Module/ServiceProvider**
```
Modules/ServiceProvider/
├── Database/Migrations/
│   ├── create_service_provider_profiles_table.php
│   └── create_service_provider_coverage_areas_table.php
├── Entities/
│   ├── ServiceProviderProfile.php
│   └── ServiceProviderCoverageArea.php
├── Enums/
│   └── ServiceProviderType.php
├── DTOs/
│   └── ServiceProviderProfileDTO.php
├── Http/
│   ├── Controllers/
│   │   └── ServiceProviderController.php
│   ├── Requests/
│   │   ├── StoreServiceProviderRequest.php
│   │   └── UpdateServiceProviderRequest.php
│   └── Resources/
│       └── ServiceProviderResource.php
├── Services/
│   └── ServiceProviderService.php
├── Policies/
│   └── ServiceProviderPolicy.php
├── Providers/
│   └── ServiceProviderServiceProvider.php
├── Routes/
│   └── api.php
└── Tests/
    └── ServiceProviderTest.php
```

**1.3 جداول قاعدة البيانات**

`service_provider_profiles`:
- `id`, `user_id` (FK), `type` (photographer/lawyer/inspector/marketer/other)
- `bio`, `experience_years`, `license_number`, `license_document` (media)
- `price_per_task` (decimal, nullable — null = تسعير حسب المهمة), `price_type` (fixed/hourly/negotiable)
- `is_verified` (boolean — admin approval), `is_available` (boolean)
- `average_rating` (decimal), `total_completed_tasks` (integer)
- `metadata` (JSON for extra fields per type)
- `timestamps`, `softDeletes`

`service_provider_coverage_areas`:
- `id`, `service_provider_id` (FK), `city_id` (FK)
- `timestamps`

**1.4 الـ API Endpoints**

Public:
```
GET  api/public/service-providers              → قائمة مقدمي الخدمات (فلترة حسب type, city)
GET  api/public/service-providers/{id}         → تفاصيل مقدم خدمة + تقييماته
GET  api/public/service-providers/{id}/reviews → تقييمات مقدم الخدمة
```

Auth (مقدم الخدمة):
```
POST   api/service-provider/register           → تسجيل كمقدم خدمة (يرفع الشهادة/الترخيص)
PUT    api/service-provider/profile             → تعديل البروفايل
GET    api/service-provider/profile             → عرض بروفايلي
PUT    api/service-provider/availability        → تفعيل/تعطيل الاستقبال
GET    api/service-provider/my-tasks            → مهامي الحالية
GET    api/service-provider/earnings            → أرباحي + كشف حساب
POST   api/service-provider/withdraw            → طلب سحب أرباح
```

Admin:
```
GET    api/admin/service-providers              → كل مقدمي الخدمات
POST   api/admin/service-providers/{id}/verify  → توثيق مقدم خدمة
POST   api/admin/service-providers/{id}/unverify→ إلغاء توثيق
```

**1.5 الصلاحيات (Permissions) الجديدة**
```php
'service_providers' => ['list', 'show', 'verify', 'unverify'],
```

**1.6 التكامل مع المراجعات**
- إعادة استخدام `Review` model الموجود — `reviewed_id` يمكن أن يكون مقدم خدمة أيضا (morph بدل FK مباشر أو إضافة `reviewed_type`).

---

## Plan 2: Verified Badge Completion (إكمال شارة التوثيق)

### الوصف
البنية التحتية للشارة موجودة (`is_verified` + `verified_badge` في الاشتراك) لكن منطق عرض/إخفاء الشارة بناء على الاشتراك غير منفذ.

### المهام

**2.1 تفعيل gating للشارة**
- في `OfficeResource` و `OfficeProfileResource` و `PropertyResource`: إظهار حقل `is_verified = true` فقط إذا كان المستخدم مشتركا في خطة تحتوي `verified_badge` مفعلة.
- إضافة check: `SubscriptionAccess::hasFeature($user, 'verified_badge')`.

**2.2 إضافة شارة للممتلكات**
- في `PropertyResource.getCustomData()`: إظهار `publisher_is_verified` بناء على ناشر العقار + اشتراكه.
- الفلترة: إضافة فلتر `?verified_only=true` على public properties API.

**2.3 صفحة المكاتب الموثقة العامة**
- `GET api/public/offices` موجودة — تأكد من فلترة `is_verified` مع `verified_badge` subscription check.

**2.4 تحسين الـ Seeder**
- عدم تغيير كبير — الشارة جاهزة في Office Pro و Enterprise حاليا.

---

## Plan 3: Service Request System (نظام طلب الخدمات)

### الوصف
نظام يسمح للمستخدمين بطلب خدمات من مقدمي الخدمات (مصور، محامي، مفتش، الخ)، مع دورة حياة كاملة للطلب وربطه بالعقار.

### المهام

**3.1 إنشاء Entity + Migration**

جدول `service_requests`:
- `id`, `client_id` (FK users), `provider_id` (FK service_provider_profiles, nullable — null = طلب مفتوح)
- `property_id` (FK nullable — للخدمات المرتبطة بعقار), `service_type` (photography/inspection/legal/marketing)
- `status` (pending/accepted/in_progress/completed/cancelled/rejected)
- `scheduled_at` (datetime — موعد الزيارة), `completed_at`, `cancelled_at`
- `client_notes`, `provider_notes`, `admin_notes`
- `price` (decimal — سعر الخدمة), `platform_fee` (decimal — نسبة المنصة), `provider_earnings` (decimal)
- `is_paid` (boolean — هل دفع العميل), `paid_at`
- `is_provider_paid` (boolean — هل استلم مقدم الخدمة مستحقاته)
- `timestamps`

جدول `service_request_tasks` (للخدمات متعددة الخطوات مثل inspection):
- `id`, `service_request_id` (FK), `task_type` (photo_upload/checklist/report/verify)
- `checklist_json` (JSON), `completed_at`
- `timestamps`

**3.2 ملفات الموديول**
```
Modules/RealEstate/ (أو ServiceProvider/)
├── Database/Migrations/
│   ├── create_service_requests_table.php
│   └── create_service_request_tasks_table.php
├── Entities/
│   ├── ServiceRequest.php
│   └── ServiceRequestTask.php
├── Enums/
│   ├── ServiceType.php
│   ├── ServiceRequestStatus.php
│   └── ServiceTaskType.php
├── DTOs/
│   └── ServiceRequestDTO.php
├── Http/
│   ├── Controllers/
│   │   ├── ClientServiceRequestController.php   (منظور العميل)
│   │   ├── ProviderServiceRequestController.php (منظور مقدم الخدمة)
│   │   └── AdminServiceRequestController.php    (منظور الأدمن)
│   ├── Requests/
│   │   ├── CreateServiceRequestRequest.php
│   │   └── CompleteTaskRequest.php
│   └── Resources/
│       ├── ServiceRequestResource.php
│       └── ServiceRequestTaskResource.php
├── Services/
│   └── ServiceRequestService.php
├── Routes/
│   └── api.php
└── Tests/
    └── ServiceRequestTest.php
```

**3.3 الـ API Endpoints**

العميل:
```
POST   api/service-requests                    → إنشاء طلب خدمة
GET    api/service-requests                    → طلباتي
GET    api/service-requests/{id}               → تفاصيل الطلب
POST   api/service-requests/{id}/cancel        → إلغاء الطلب
POST   api/service-requests/{id}/confirm       → تأكيد إتمام الخدمة (يقبل العمل)
POST   api/service-requests/{id}/rate          → تقييم مقدم الخدمة
```

مقدم الخدمة:
```
GET    api/provider/service-requests           → الطلبات المتاحة + طلباتي
POST   api/provider/service-requests/{id}/accept  → قبول طلب
POST   api/provider/service-requests/{id}/reject  → رفض طلب
POST   api/provider/service-requests/{id}/complete→ إنهاء الخدمة (يرفع التقرير/الصور)
POST   api/provider/service-requests/{id}/upload-media → رفع صور/ملفات
```

Admin:
```
GET    api/admin/service-requests              → مراقبة كل الطلبات
POST   api/admin/service-requests/{id}/resolve-dispute → حل نزاع
```

**3.4 الصلاحيات**
```php
'service_requests' => ['list', 'show', 'create', 'cancel', 'manage-disputes'],
```

**3.5 دورة حياة الطلب (Status Flow)**

```
PENDING ──→ ACCEPTED ──→ IN_PROGRESS ──→ COMPLETED
   │            │              │
   └─→ CANCELLED│              └─→ CANCELLED
                └─→ REJECTED
```

---

## Plan 4: Property Inspection & Verification Workflow (توثيق العقار عبر المعاينة)

### الوصف
إضافة مسار توثيق عقار عبر مفتش ميداني يزور العقار ويتأكد من تطابق الصور والوصف والملكية، ثم يرفع تقريره لمراجعة الأدمن.

### المهام

**4.1 تمديد Property**
- إضافة `PropertyStatus::UNDER_INSPECTION` إلى enum.
- إضافة `inspection_requested_at`, `inspection_completed_at` إلى properties table.
- إضافة `inspection_score` (1-10), `inspection_report_json` إلى properties.
- إضافة `is_physically_verified` (boolean) — يظهر للمستخدمين أن العقار تمت معاينته.

**4.2 إضافة PropertyStatus::UNDER_INSPECTION**
```php
case UNDER_INSPECTION = 'under_inspection';
```

**4.3 ربط الـ ServiceRequest بالعقار**
- عند إنشاء ServiceRequest بـ `service_type = inspection` و `property_id = X`:
  1. يتغير حالة العقار تلقائيا إلى `under_inspection`.
  2. بعد اكتمال الخدمة ومراجعة الأدمن، يتغير إلى `approved` أو `rejected`.

**4.4 واجهة تقرير المفتش**
- `checklist_json` يحتوي على:
```json
{
  "photos_match": true,
  "description_accurate": true,
  "ownership_verified": true,
  "property_condition": "excellent",
  "actual_rooms": 3,
  "actual_bathrooms": 2,
  "actual_area": 150,
  "notes": "العقار مطابق للوصف والصور",
  "photos_taken": ["uuid1", "uuid2"]
}
```

**4.5 نقاط التكامل**
- `ServiceRequestService` ← يستدعي `PropertyStatusService` عند اكتمال المعاينة.
- الأدمن يراجع التقرير في لوحة التحكم ويوافق/يرفض.
- `PropertyResource` يعرض `is_physically_verified` + `inspection_score` عند الطلب.

---

## Plan 5: Provider Billing & Payments (محاسبة مقدمي الخدمات)

### الوصف
نظام دفع متكامل مبني على Ledger الموجود:
- العميل يدفع للمنصة.
- المنصة تحتفظ بنسبة (commission).
- الباقي يودع في محفظة مقدم الخدمة.
- مقدم الخدمة يطلب سحب أرباحه.

### المهام

**5.1 حساب Commission Config**
- جدول `service_commission_configs`:
  - `id`, `service_type`, `commission_type` (percentage/fixed), `commission_value`
  - مثال: photography → 20%, inspection → 15%, legal → 10%

**5.2 تدفق الدفع عند ServiceRequest**

سيناريو الدفع عند إنشاء خدمة:
```
1. العميل ينشئ ServiceRequest ويختار Provider.
2. النظام يحجز المبلغ من محفظة العميل (hold).
3. مقدم الخدمة يقبل الطلب.
4. عند اكتمال الخدمة وتأكيد العميل:
   a. المبلغ يُخصم من العميل (debit from client wallet).
   b. عمولة المنصة تحول إلى platform_fee account (transfer).
   c. باقي المبلغ يحول إلى محفظة مقدم الخدمة (credit provider wallet).
5. إذا ألغي الطلب قبل البدء → يفك الحجز (release hold).
```

**5.3 Service Methods الجديدة**

`ProviderBillingService`:
```php
holdPayment(ServiceRequest $request): void      // حجز المبلغ عند إنشاء الطلب
releasePayment(ServiceRequest $request): void    // فك الحجز عند الإلغاء
settlePayment(ServiceRequest $request): void     // تسوية المبلغ عند الاكتمال
getProviderBalance(User $provider): float        // رصيد مقدم الخدمة
getEarningsHistory(User $provider): Collection  // سجل الأرباح
```

**5.4 نقاط التكامل مع Ledger**
- `reference_type` الجديد في AccountEntry: `service_payment`.
- حساب `PLATFORM_FEE` و `CLEARING` موجودان مسبقا وجاهزان للاستخدام.

**5.5 سحب الأرباح (Withdrawal)**
- `POST api/service-provider/withdraw { amount, bank_details }`
- الأدمن يراجع طلب السحب ويوافق عليه يدويا أو آليا إذا كان المبلغ أقل من حد معين.
- إنشاء `AccountEntry` من نوع `DEBIT` على محفظة مقدم الخدمة.

---

## Plan 6: Photographer-on-Request — إنشاء عقار مخفي + طلب مصور

### الوصف
فكرة المستخدم: إنشاء عقار بحالة مخفية (`draft`)، ثم طلب مصور من المنصة لتصويره، ثم بعد رفع الصور يتحول إلى `pending` للمراجعة.

### المهام

**6.1 إضافة PropertyStatus::DRAFT**
```php
case DRAFT = 'draft';
```
- العقار بحالة draft لا يظهر في أي public API.
- فقط الناشر يرى مسوداته في `GET api/my-properties?status=draft`.

**6.2 تدفق إنشاء عقار + طلب مصور (واجهة واحدة)**

```
POST api/properties (مع status=draft + request_photographer=true)
→ ينشئ العقار بحالة draft
→ ينشئ ServiceRequest تلقائيا بنوع photography
→ المصور يقبل ويذهب للموقع ويصور
→ يرفع الصور على نفس العقار عبر media API
→ العقار ينتقل إلى pending تلقائيا
→ الأدمن يراجع ← approved
```

**6.3 API التكامل (Composite Endpoint)**
```
POST api/properties/with-photographer
Body:
{
  "name": "...", "description": "...",
  "property_type": "apartment", "type_of_contract": "sale",
  "country_id": 1, "city_id": 5,
  "rooms": 3, "bathrooms": 2, "area": 150,
  "price": 250000, "currency": "SAR",
  "scheduled_at": "2026-06-25 10:00:00",
  "provider_id": 5,  // optional - إذا اختار مصور معين
  "notes": "الباب الخلفي مفتوح"
}
```

**6.4 الـ Response**
```json
{
  "data": {
    "property": { ...PropertyResource },
    "service_request": { ...ServiceRequestResource }
  },
  "message": "Property saved as draft and photographer requested"
}
```

---

## ملخص الخطط وترتيب التنفيذ المقترح

| # | الخطة | الوقت المقدر | المتطلب السابق |
|---|-------|-------------|----------------|
| 1 | **Service Providers Module** | 3-4 ساعات | لا شيء |
| 2 | **Verified Badge Completion** | 1 ساعة | لا شيء |
| 3 | **Service Request System** | 3-4 ساعات | Plan 1 |
| 4 | **Property Inspection Workflow** | 2-3 ساعات | Plan 3 + Plan 1 |
| 5 | **Provider Billing & Payments** | 2-3 ساعات | Plan 3 + Plan 1 |
| 6 | **Photographer-on-Request** | 1-2 ساعات | Plan 1 + Plan 3 |

**الترتيب المنطقي:**
1 → 2 → 3 (بالتوازي مع 2) → 4 + 5 (بالتوازي) → 6

---

## كيفية الاستخدام

قل لي مثلا:
- **"نفذ Plan 1"** — أبني موديول مقدمي الخدمات كاملا.
- **"نفذ Plan 3"** — أبني نظام طلب الخدمات.
- **"نفذ Plan 1 + Plan 3"** — أبني الاثنين معا.

كل خطة تُنفذ بشكل مستقل وتعمل directly بعد الانتهاء منها (بعد `composer setup`).
