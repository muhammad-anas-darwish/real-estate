# المهمة #29: اختبارات شاملة + توثيق Scribe + تحديثات README

> **التقرير المصدر:** `docs/ideas/statistics-and-dashboards/report.md` (معايير القبول الكاملة في السطور 230–270)
> **الهدف:** إضافة اختبارات E2E شاملة لكل endpoints الإحصائيات، توثيق Scribe لكل الـ endpoints، تحديث `docs/frontend/` و README الخاص بمشروع الـ docs/، إنشاء `docs/frontend/statistics-dashboard.md` كمرجع للـ frontend.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟢 منخفضة
> **الجهد المقدّر:** 4–6 ساعات
> **الاعتمادية:** يجب أن تسبقها الخطط #26، #27، #28.
> **راجع:** `docs/ideas/statistics-and-dashboards/report.md` (كل معايير القبول)، `docs/frontend/frontend-api-reference.md` (نمط التوثيق الموجود)

---

## الوضع الحالي

بعد اكتمال الخطط #26، #27، #28، يكون لدينا:
- 18 endpoint للإحصائيات (10 trader + 1 property + 7 market + 7 admin = 25 endpoint فعلياً، 18 unique).
- اختبارات منفصلة لكل لوحة (TraderDashboardTest, PropertyStatsTest, MarketStatsTest, AdminDashboardTest).
- لكن لا يوجد:
  - E2E test يحاكي رحلة كاملة (تاجر يدخل → يرى KPIs → يفلتر → يقارن → يصدر CSV).
  - توثيق Scribe للـ endpoints.
  - ملف مرجعي للـ frontend يشرح بنية الاستجابة وكيف يعرضها.

---

## المرحلة 1: E2E test للرحلة الكاملة (~ 1.5 ساعة)

`Modules/Statistics/Tests/TraderJourneyTest.php`:

```php
<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Entities\Review;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\ViewingStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TraderJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'statistics.view', 'statistics.export',
            'leads.list', 'leads.show', 'leads.create', 'leads.edit',
            'leads.change-status', 'leads.archive', 'leads.restore', 'leads.export',
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $trader = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $trader->givePermissionTo($permissions);

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_complete_trader_dashboard_journey(): void
    {
        // Setup: 3 properties, 5 leads, 10 views, 2 reviews
        $properties = Property::factory()->count(3)->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
        ]);

        PropertyView::factory()->count(10)->create([
            'property_id' => $properties->first()->id,
            'created_at' => now()->subDays(2),
        ]);

        Lead::factory()->count(3)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::NEW,
            'created_at' => now()->subDays(2),
        ]);
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::WON,
            'created_at' => now()->subDays(2),
        ]);

        Review::factory()->count(2)->create([
            'reviewed_id' => $this->trader->id,
            'rating' => 5,
        ]);

        // === Step 1: Trader opens summary ===
        $summary = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');
        $summary->assertStatus(200);

        $kpis = collect($summary->json('data.kpis'));
        $this->assertEquals(3, $kpis->firstWhere('key', 'active_properties')['value']);
        $this->assertEquals(10, $kpis->firstWhere('key', 'total_views')['value']);
        $this->assertEquals(5, $kpis->firstWhere('key', 'new_leads')['value']);
        $this->assertEquals(5, $kpis->firstWhere('key', 'average_rating')['value']);

        // === Step 2: Trader views views trend ===
        $trend = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/views-trend?period=last_30_days');
        $trend->assertStatus(200);
        $this->assertCount(30, $trend->json('data.points'));

        // === Step 3: Trader views leads by status ===
        $leadsByStatus = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/leads-by-status?period=last_30_days');
        $leadsByStatus->assertStatus(200);
        $this->assertEquals(5, $leadsByStatus->json('data.total'));

        // === Step 4: Trader changes period to last_7_days ===
        $shortSummary = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_7_days');
        $shortSummary->assertStatus(200);
        // Same values (data is recent)
        $this->assertEquals(5, collect($shortSummary->json('data.kpis'))
            ->firstWhere('key', 'new_leads')['value']);

        // === Step 5: Trader clicks top properties ===
        $topProps = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/top-properties?period=last_30_days&limit=5');
        $topProps->assertStatus(200);
        $this->assertEquals(3, count($topProps->json('data.items')));

        // === Step 6: Trader exports CSV ===
        $csv = $this->actingAs($this->trader)
            ->get('/api/dashboard/trader/export/properties?period=last_30_days');
        $csv->assertStatus(200);
        $csv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $lines = explode("\n", trim($csv->streamedContent()));
        $this->assertCount(4, $lines); // header + 3 properties
    }

    public function test_trader_with_no_data_sees_zero_kpis(): void
    {
        $summary = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');

        $summary->assertStatus(200);
        $kpis = collect($summary->json('data.kpis'));
        $this->assertEquals(0, $kpis->firstWhere('key', 'active_properties')['value']);
        $this->assertEquals(0, $kpis->firstWhere('key', 'new_leads')['value']);
    }

    public function test_period_filtering_changes_results(): void
    {
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'created_at' => now()->subDays(60),
        ]);
        Property::factory()->create([
            'publisher_id' => $this->trader->id,
            'status' => PropertyStatus::APPROVED,
            'created_at' => now()->subDays(2),
        ]);

        // last_30_days: 1 property
        $shortResponse = $this->actingAs($this->trader)
            ->getJson('/api/dashboard/trader/summary?period=last_30_days');
        $kpis = collect($shortResponse->json('data.kpis'));
        // Note: active_properties is not period-scoped, so it's still 2
        // But views are period-scoped
        $this->assertEquals(0, $kpis->firstWhere('key', 'total_views')['value']);
    }
}
```

---

## المرحلة 2: توثيق Scribe للـ endpoints (~ 2 ساعة)

### 2.1 Scribe config (تحقق من الإعدادات)

`config/scribe.php` يجب أن يكون معدّاً. إذا لم يكن، نضيف في `Modules/Statistics/Providers/StatisticsServiceProvider::boot()`:

```php
public function boot(): void
{
    $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

    // Scribe group
    if (class_exists(\Knuckles\Scribe\Scribe::class)) {
        \Knuckles\Scribe\Scribe::addGroup(function ($group) {
            $group->setName('Statistics');
            $group->setDescription('إحصائيات شاملة: لوحة التاجر، إحصائيات العقار، إحصائيات السوق، لوحة الأدمن.');
        });
    }
}
```

### 2.2 توثيق inline في الـ Controllers

إضافة docblocks على كل action method:

```php
/**
 * ملخّص الإحصائيات للتاجر.
 *
 * يُعيد 7 بطاقات KPI تشمل: العقارات الفعّالة، إجمالي المشاهدات،
 * العملاء الجدد، معدل التحويل، متوسط التقييم، عقود الإيجار النشطة،
 * والمواعيد القادمة (هذا الأسبوع). كل KPI يقارن بالفترة السابقة تلقائياً.
 *
 * @group Statistics
 *
 * @authenticated
 * @queryParam period string الفترة الزمنية. افتراضياً last_30_days. القيم: today, yesterday, last_7_days, last_30_days, this_week, last_week, this_month, last_month, this_year, custom. Example: last_30_days
 * @queryParam from string تاريخ البداية (مطلوب فقط عند period=custom). Example: 2026-01-01
 * @queryParam to string تاريخ النهاية (مطلوب فقط عند period=custom). Example: 2026-01-31
 *
 * @response 200 {
 *   "success": true,
 *   "data": {
 *     "filter": {...},
 *     "generated_at": "2026-07-24T12:00:00+00:00",
 *     "kpis": [
 *       {"key": "active_properties", "label": "العقارات الفعّالة", "value": 5, "previous_value": 3, "change_percent": 66.67, "change_direction": "up", "format": "number", "icon": "building"}
 *     ]
 *   }
 * }
 */
public function summary(Request $request): JsonResponse
```

> **ملاحظة:** التوثيق الكامل لكل action في `Modules/Statistics/Http/Controllers/TraderDashboardController.php`, `PropertyStatsController.php`, `MarketStatsController.php`, `AdminDashboardController.php` — يُضاف docblock مختصر مع `@group Statistics` على كل method.

### 2.3 توليد التوثيق

```bash
php artisan scribe:generate
```

يجب أن تظهر كل الـ endpoints الإحصائية في `/docs` تحت مجموعة "Statistics".

---

## المرحلة 3: مرجع Frontend (~ 1.5 ساعة)

`docs/frontend/statistics-dashboard.md`:

```markdown
# مرجع لوحة الإحصائيات (Statistics Dashboard) — دليل الفرونت

> **الإصدار:** 1.0  
> **التاريخ:** 2026-07-24  
> **الـ backend:** `Modules/Statistics/`

## نظرة عامة
أربع واجهات منفصلة، كل واحدة لها endpoint group:

| الواجهة | Prefix | Auth | الوصف |
|---------|--------|:---:|-------|
| لوحة التاجر | `/api/dashboard/trader/*` | trader role | KPIs شخصية، رسوم، قوائم، مقارنة، CSV |
| إحصائيات العقار | `/api/dashboard/properties/{id}/stats` | أي مستخدم مسجّل | تفاصيل عقار واحد: قمع، تقييمات، منافسون |
| إحصائيات السوق | `/api/market/*` | **public** | بيانات مجمّعة للزائرين |
| لوحة الأدمن | `/api/admin/statistics/*` | permission: admin_statistics.view | كل المنصّة |

## البنية الموحّدة للاستجابة
كل endpoints الإحصائيات ترجع:

```json
{
  "success": true,
  "data": {
    "filter": {
      "period": "last_30_days",
      "range": {"from": "2026-06-24", "to": "2026-07-24", "days": 30},
      "previous_range": {"from": "2026-05-25", "to": "2026-06-23", "days": 30}
    },
    "generated_at": "2026-07-24T12:00:00+00:00",
    "kpis": [...],
    "charts": [...],
    "distributions": {...},
    "lists": {...}
  }
}
```

## بطاقات KPI
```json
{
  "key": "total_views",
  "label": "إجمالي المشاهدات",
  "value": 1234,
  "previous_value": 1000,
  "change_percent": 23.4,
  "change_direction": "up",
  "format": "number",
  "icon": "eye"
}
```

`format` القيم الممكنة:
- `number` — عرض كرقم عادي
- `percent` — عرض كنسبة مئوية مع علامة %
- `currency` — عرض كعملة (من price.currency)
- `rating` — عرض كنجوم (من 5)
- `duration` — عرض كزمن (ساعات/أيام)

`change_direction`:
- `up` — سهم أخضر (تحسّن)
- `down` — سهم أحمر (تراجع)
- `flat` — خط مستقيم (ثابت)
- `null` — لا مقارنة (لفترات لا تدعم المقارنة)

## الرسوم البيانية
```json
{
  "metric": "property_views",
  "unit": "count",
  "points": [
    {"date": "2026-07-01", "count": 10},
    {"date": "2026-07-02", "count": 15}
  ]
}
```

`unit`:
- `count` — عدد
- `currency` — مبالغ مالية
- `percent` — نسب

## التوزيعات (Distributions)
```json
{
  "dimension": "lead_status",
  "items": [
    {"key": "new", "label": "جديد", "count": 3, "percentage": 60.0},
    {"key": "won", "label": "تم البيع", "count": 2, "percentage": 40.0}
  ],
  "total": 5
}
```

## القوائم (Top Lists)
```json
{
  "title": "أفضل العقارات أداءً",
  "items": [
    {"id": 1, "name": "...", "views_in_range": 100, "favorites_count": 5}
  ],
  "sort_by": "views_in_range"
}
```

## فلاتر الفترة
كل endpoints الإحصائيات تقبل:
- `?period=today|yesterday|last_7_days|last_30_days|this_week|last_week|this_month|last_month|this_year|custom`
- `?from=YYYY-MM-DD&to=YYYY-MM-DD` (مطلوب عند `period=custom`)

الافتراضي: `last_30_days`.

## الألوان والاتجاهات (لـ frontend)
```css
.kpi-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.kpi-card .value { font-size: 2rem; font-weight: bold; }
.kpi-card .change.up { color: #10b981; }     /* أخضر */
.kpi-card .change.down { color: #ef4444; }   /* أحمر */
.kpi-card .change.flat { color: #6b7280; }   /* رمادي */
```

## حالة فارغة
عندما لا توجد بيانات:
- `kpis[].value` = 0
- `charts[].points` = مصفوفة بنقاط (count = 0)
- `distributions[].items` = مصفوفة بالعناصر (count = 0)
- `lists[].items` = مصفوفة فارغة

## الـ Rate Limiting
كل endpoints الإحصائية تخضع لـ default API rate limiting (60 requests/minute). لا حاجة لتعريف منفصل.

## Caching
- الـ backend يخزّن الإحصائيات مؤقتاً لمدة 5 دقائق (trader, property) أو 10 دقائق (market) أو 5 دقائق/ساعة (admin حسب نوع البيانات).
- لا حاجة للـ frontend لتخزين مؤقت — استدعِ مباشرة كل 5 دقائق أو عند refresh.

## تصدير CSV
- `GET /api/dashboard/trader/export/properties?period=last_30_days` — يُنزّل CSV مع `Content-Disposition: attachment`.
- الـ frontend يستدعيها كـ `<a href="..." download>` عادية.
```

---

## المرحلة 4: تحديث README الخاص بالـ docs (~ 30 دقيقة)

تحديث `docs/frontend/README.md` (إن وُجد) بإضافة رابط للملف الجديد.

تحديث `docs/reports/README.md` — إضافة قسم statistics-and-dashboards.

تحديث `docs/plans/README.md` (راجع المرحلة 5 أدناه).

---

## المرحلة 5: تحديث `docs/plans/README.md` (~ 30 دقيقة)

استبدال/إضافة قسم للإحصائيات في `docs/plans/README.md` بعد قسم per-user-file-system:

```md
### statistics-and-dashboards (تقرير: `docs/ideas/statistics-and-dashboards/report.md`)
* [plan25-statistics-foundation.md](25-statistics-foundation.md)... ❌
* [plan26-trader-dashboard.md](26-trader-dashboard.md)... ❌
* [plan27-property-and-market-stats.md](27-property-and-market-stats.md)... ❌
* [plan28-admin-dashboard.md](28-admin-dashboard.md)... ❌
* [plan29-statistics-tests-and-docs.md](29-statistics-tests-and-docs.md)... ❌
```

إضافة إلى "خريطة التبعيات" قسم للإحصائيات:

```md
### خريطة الإحصائيات (تقرير statistics-and-dashboards)

```
#25 (Foundation)  ──→  #26 (Trader)        ─┐
        │                                    │
        ├──→  #27 (Property & Market)        ├──→  #29 (Tests & Docs)
        │                                    │
        └──→  #28 (Admin)                    ─┘
```

تحديث قسم "موجات التنفيذ" لتشمل الموجة الأولى من الإحصائيات (#25) والموجة الثانية (#26, #27, #28 بالتوازي).

تحديث "ملخّص الحالة الراهنة" ليعكس 20 خطة إجمالاً.

تحديث جدول "جدول التنفيذ المقترح" لإضافة مسار D (الإحصائيات).

---

## المرحلة 6: التحقق النهائي (~ 30 دقيقة)

```bash
composer pint

php artisan config:clear
php artisan test --testsuite=Modules --filter="Statistics|TraderDashboard|MarketStats|PropertyStats|AdminDashboard|TraderJourney"
```

كل الاختبارات يجب أن تنجح.

```bash
php artisan scribe:generate
```

التوثيق يجب أن يُولّد بدون أخطاء.

```bash
php artisan route:list --path=api | grep -E "trader|market|admin/statistics|properties/.*stats"
```

كل الـ 25 endpoint يجب أن تكون مسجّلة.

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (E2E test) | مستقل | #25, #26, #27, #28 |
| 2 (Scribe docs) | كل controller مستقل | #25, #26, #27, #28 |
| 3 (Frontend guide) | ملف جديد مستقل | #25, #26, #27, #28 |
| 4 (README updates) | مستقل | #25, #26, #27, #28 |
| 5 (Plans README) | مستقل | #25, #26, #27, #28 |
| 6 (Final verify) | يعتمد على 1-5 | 1-5 |

---

## معايير القبول

- [ ] `Modules/Statistics/Tests/TraderJourneyTest.php` يحوي 3 E2E tests (رحلة كاملة، بدون بيانات، فلتر فترة).
- [ ] كل action في الـ 4 controllers يحوي docblock مع `@group Statistics`.
- [ ] `php artisan scribe:generate` ينجح ويُنتج توثيق لكل endpoints الإحصائية.
- [ ] `docs/frontend/statistics-dashboard.md` موجود (200+ سطر) ويشرح بنية الاستجابة.
- [ ] `docs/plans/README.md` يحوي قسم `statistics-and-dashboards` مع 5 خطط.
- [ ] `docs/reports/README.md` يحوي رابط للتقرير.
- [ ] `composer pint` يمر بدون أخطاء.
- [ ] `php artisan test --testsuite=Modules --filter=Statistics` ينجح كل الاختبارات.
- [ ] `php artisan route:list` يُظهر 25 endpoint إحصائي.
- [ ] `php artisan scribe:generate` يُنتج ملف `docs/index.html` بدون أخطاء.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
