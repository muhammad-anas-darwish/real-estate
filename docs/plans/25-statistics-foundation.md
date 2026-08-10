# المهمة #25: تأسيس البنية التحتية للإحصائيات (Statistics Foundation)

> **التقرير المصدر:** `docs/ideas/statistics-and-dashboards/report.md` (السيناريوهات 1–4، القواعد 1–10، معايير القبول — الميزات الأفقية المشتركة)
> **الهدف:** إنشاء الوحدة `Modules/Statistics/` مع البنية التحتية المشتركة التي ستعتمد عليها الخطط #26 (لوحة التاجر)، #27 (إحصائيات العقار والسوق)، #28 (لوحة الأدمن). يشمل: Period enum، DateRange value object، StatsCacheHelper، StatsResponseBuilder، Resources موحّدة (KPI Card, Time Series, Distribution, Top List)، Permissions، بنية المجلدات.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟡 عالية
> **الجهد المقدّر:** 4–6 ساعات
> **الاعتمادية:** لا شيء — هذه الخطة هي الأساس.
> **راجع:** `docs/ideas/statistics-and-dashboards/report.md` (السطور 80–120 "الميزات الأفقية"، السطور 220–240 "معايير القبول العامة")

---

## الوضع الحالي

لا توجد وحدة `Modules/Statistics/` في النظام. الإحصائيات الحالية موزّعة:
- `LeadStatsService` في `Modules/Crm/Services/` (يخدم الـ CRM فقط).
- عدّادات بسيطة على `properties.views` (cache فوري عبر increment).
- جداول تسجيل: `property_views`, `ad_views`, `ad_visits`، لكن لا تُقرأ عبر services موحّدة.

هذه الخطة تنشئ:
- وحدة `Modules/Statistics/` كاملة.
- Period enum + DateRange + DTO للفلترة الموحّدة.
- CACHE_TTL و CACHE_TAGS متّفق عليها.
- Resource classes موحّدة لـ API responses.
- Permission واحد: `statistics.view` (للوحة التاجر) + `admin_statistics.view` (للوحة الأدمن).
- "Stub" controllers فارغة تُملأ في الخطط اللاحقة.

---

## المرحلة 1: هيكل الوحدة + ServiceProvider (~ 30 دقيقة)

### 1.1 هيكل المجلدات

```
Modules/Statistics/
├── Providers/
│   └── StatisticsServiceProvider.php
├── Database/
│   └── (لا شيء في هذه الخطة)
├── Routes/
│   └── api.php
├── Http/
│   ├── Controllers/
│   │   ├── TraderDashboardController.php      (stub)
│   │   ├── PropertyStatsController.php        (stub)
│   │   ├── MarketStatsController.php          (stub)
│   │   └── AdminDashboardController.php       (stub)
│   └── Resources/
│       ├── KpiCardResource.php
│       ├── TimeSeriesResource.php
│       ├── DistributionResource.php
│       └── TopListResource.php
├── Services/
│   ├── StatsCacheHelper.php
│   ├── PeriodResolver.php
│   ├── TraderStatsService.php                 (stub)
│   ├── PropertyStatsService.php               (stub)
│   ├── MarketStatsService.php                 (stub)
│   └── AdminStatsService.php                  (stub)
├── ValueObjects/
│   └── DateRange.php
├── Enums/
│   └── StatsPeriod.php
├── DTOs/
│   └── StatsFilterDTO.php
└── Tests/
    └── FoundationTest.php
```

### 1.2 `StatisticsServiceProvider`

`Modules/Statistics/Providers/StatisticsServiceProvider.php`:

```php
<?php

namespace Modules\Statistics\Providers;

use Illuminate\Support\ServiceProvider;

class StatisticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
```

> **ملاحظة:** لا حاجة لـ `$this->loadMigrationsFrom` في هذه الخطة (لا جداول جديدة). تُسجَّل عبر Laravel auto-discovery بفضل namespace `Modules\\Statistics\\` الموجود في `composer.json`.

### 1.3 Routes skeleton

`Modules/Statistics/Routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Statistics\Http\Controllers\TraderDashboardController;
use Modules\Statistics\Http\Controllers\PropertyStatsController;
use Modules\Statistics\Http\Controllers\MarketStatsController;
use Modules\Statistics\Http\Controllers\AdminDashboardController;

// Trader dashboard (auth:sanctum + trader role)
Route::prefix('api/dashboard/trader')->middleware(['auth:sanctum', 'trader'])->group(function () {
    Route::get('summary', [TraderDashboardController::class, 'summary']);
    Route::get('views-trend', [TraderDashboardController::class, 'viewsTrend']);
    Route::get('leads-by-status', [TraderDashboardController::class, 'leadsByStatus']);
    Route::get('properties-by-status', [TraderDashboardController::class, 'propertiesByStatus']);
    Route::get('top-properties', [TraderDashboardController::class, 'topProperties']);
    Route::get('recent-leads', [TraderDashboardController::class, 'recentLeads']);
    Route::get('upcoming-appointments', [TraderDashboardController::class, 'upcomingAppointments']);
    Route::get('expiring-rentals', [TraderDashboardController::class, 'expiringRentals']);
    Route::get('sponsored-ads-summary', [TraderDashboardController::class, 'sponsoredAdsSummary']);
    Route::get('export/properties', [TraderDashboardController::class, 'exportProperties']);
});

// Per-property statistics (auth:sanctum)
Route::prefix('api/dashboard/properties')->middleware(['auth:sanctum'])->group(function () {
    Route::get('{property}/stats', [PropertyStatsController::class, 'show']);
});

// Market statistics (PUBLIC — no auth)
Route::prefix('api/market')->group(function () {
    Route::get('overview', [MarketStatsController::class, 'overview']);
    Route::get('by-city', [MarketStatsController::class, 'byCity']);
    Route::get('by-category', [MarketStatsController::class, 'byCategory']);
    Route::get('by-price-range', [MarketStatsController::class, 'byPriceRange']);
    Route::get('top-viewed', [MarketStatsController::class, 'topViewed']);
    Route::get('top-saved', [MarketStatsController::class, 'topSaved']);
    Route::get('listings-trend', [MarketStatsController::class, 'listingsTrend']);
});

// Admin dashboard (auth:sanctum + permission: admin_statistics.view)
Route::prefix('api/admin/statistics')->middleware(['auth:sanctum', 'permission:admin_statistics.view'])->group(function () {
    Route::get('overview', [AdminDashboardController::class, 'overview']);
    Route::get('properties', [AdminDashboardController::class, 'properties']);
    Route::get('crm', [AdminDashboardController::class, 'crm']);
    Route::get('ads', [AdminDashboardController::class, 'ads']);
    Route::get('subscriptions', [AdminDashboardController::class, 'subscriptions']);
    Route::get('moderation', [AdminDashboardController::class, 'moderation']);
    Route::get('communication', [AdminDashboardController::class, 'communication']);
});
```

> **ملاحظة:** كل الـ endpoints أعلاه stubs في هذه الخطة (تُملأ في #26، #27، #28). الهدف هنا هو تجهيز البنية والـ routes فقط.

---

## المرحلة 2: Period Enum + DateRange + DTO (~ 1.5 ساعة)

### 2.1 `StatsPeriod` enum

`Modules/Statistics/Enums/StatsPeriod.php`:

```php
<?php

namespace Modules\Statistics\Enums;

enum StatsPeriod: string
{
    case TODAY = 'today';
    case YESTERDAY = 'yesterday';
    case LAST_7_DAYS = 'last_7_days';
    case LAST_30_DAYS = 'last_30_days';
    case THIS_WEEK = 'this_week';
    case LAST_WEEK = 'last_week';
    case THIS_MONTH = 'this_month';
    case LAST_MONTH = 'last_month';
    case THIS_YEAR = 'this_year';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::TODAY => 'اليوم',
            self::YESTERDAY => 'أمس',
            self::LAST_7_DAYS => 'آخر 7 أيام',
            self::LAST_30_DAYS => 'آخر 30 يوم',
            self::THIS_WEEK => 'هذا الأسبوع',
            self::LAST_WEEK => 'الأسبوع الماضي',
            self::THIS_MONTH => 'هذا الشهر',
            self::LAST_MONTH => 'الشهر الماضي',
            self::THIS_YEAR => 'هذا العام',
            self::CUSTOM => 'فترة مخصصة',
        };
    }

    public function days(): int
    {
        return match ($this) {
            self::TODAY, self::YESTERDAY => 1,
            self::LAST_7_DAYS, self::THIS_WEEK, self::LAST_WEEK => 7,
            self::LAST_30_DAYS, self::THIS_MONTH, self::LAST_MONTH => 30,
            self::THIS_YEAR => 365,
            self::CUSTOM => 0,
        };
    }

    public function supportsComparison(): bool
    {
        return ! in_array($this, [self::TODAY, self::YESTERDAY, self::CUSTOM], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### 2.2 `DateRange` value object

`Modules/Statistics/ValueObjects/DateRange.php`:

```php
<?php

namespace Modules\Statistics\ValueObjects;

use Carbon\CarbonImmutable;
use Modules\Statistics\Enums\StatsPeriod;

final readonly class DateRange
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {
        if ($from->gt($to)) {
            throw new \InvalidArgumentException('DateRange: from must be <= to');
        }
    }

    public function days(): int
    {
        return max(1, (int) $this->from->diffInDays($this->to) + 1);
    }

    public function previousRange(): self
    {
        $diff = $this->days();
        return new self(
            $this->from->subDays($diff),
            $this->to->subDays($diff),
        );
    }

    public function contains(CarbonImmutable $date): bool
    {
        return $date->between($this->from, $this->to);
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'days' => $this->days(),
        ];
    }

    public static function fromPeriod(StatsPeriod $period): self
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            StatsPeriod::TODAY => new self($now->startOfDay(), $now->endOfDay()),
            StatsPeriod::YESTERDAY => new self(
                $now->subDay()->startOfDay(),
                $now->subDay()->endOfDay()
            ),
            StatsPeriod::LAST_7_DAYS => new self(
                $now->subDays(6)->startOfDay(),
                $now->endOfDay()
            ),
            StatsPeriod::LAST_30_DAYS => new self(
                $now->subDays(29)->startOfDay(),
                $now->endOfDay()
            ),
            StatsPeriod::THIS_WEEK => new self(
                $now->startOfWeek(),
                $now->endOfWeek()
            ),
            StatsPeriod::LAST_WEEK => new self(
                $now->subWeek()->startOfWeek(),
                $now->subWeek()->endOfWeek()
            ),
            StatsPeriod::THIS_MONTH => new self(
                $now->startOfMonth(),
                $now->endOfMonth()
            ),
            StatsPeriod::LAST_MONTH => new self(
                $now->subMonth()->startOfMonth(),
                $now->subMonth()->endOfMonth()
            ),
            StatsPeriod::THIS_YEAR => new self(
                $now->startOfYear(),
                $now->endOfYear()
            ),
            StatsPeriod::CUSTOM => throw new \InvalidArgumentException('CUSTOM requires from/to'),
        };
    }

    public static function custom(string $from, string $to): self
    {
        return new self(
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->endOfDay()
        );
    }
}
```

### 2.3 `StatsFilterDTO`

`Modules/Statistics/DTOs/StatsFilterDTO.php`:

```php
<?php

namespace Modules\Statistics\DTOs;

use App\Interfaces\DTOInterface;
use Carbon\Carbon;
use Modules\Statistics\Enums\StatsPeriod;
use Modules\Statistics\ValueObjects\DateRange;

readonly final class StatsFilterDTO implements DTOInterface
{
    public function __construct(
        public StatsPeriod $period,
        public DateRange $range,
        public ?DateRange $previousRange = null,
        public ?int $cityId = null,
        public ?int $countryId = null,
        public ?int $categoryId = null,
        public ?int $publisherId = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        $periodValue = $data['period'] ?? StatsPeriod::LAST_30_DAYS->value;
        $period = StatsPeriod::from($periodValue);

        $range = $period === StatsPeriod::CUSTOM
            ? DateRange::custom($data['from'], $data['to'])
            : DateRange::fromPeriod($period);

        $previousRange = $period->supportsComparison()
            ? $range->previousRange()
            : null;

        return new self(
            period: $period,
            range: $range,
            previousRange: $previousRange,
            cityId: isset($data['city_id']) ? (int) $data['city_id'] : null,
            countryId: isset($data['country_id']) ? (int) $data['country_id'] : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            publisherId: isset($data['publisher_id']) ? (int) $data['publisher_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'period' => $this->period->value,
            'range' => $this->range->toArray(),
            'previous_range' => $this->previousRange?->toArray(),
            'city_id' => $this->cityId,
            'country_id' => $this->countryId,
            'category_id' => $this->categoryId,
            'publisher_id' => $this->publisherId,
        ];
    }

    public function days(): int
    {
        return $this->range->days();
    }
}
```

### 2.4 `PeriodResolver` helper

`Modules/Statistics/Services/PeriodResolver.php`:

```php
<?php

namespace Modules\Statistics\Services;

use Illuminate\Http\Request;
use Modules\Statistics\DTOs\StatsFilterDTO;
use Modules\Statistics\Enums\StatsPeriod;

class PeriodResolver
{
    public function fromRequest(Request $request): StatsFilterDTO
    {
        $data = $request->only([
            'period', 'from', 'to',
            'city_id', 'country_id', 'category_id', 'publisher_id',
        ]);

        return StatsFilterDTO::fromRequest($data);
    }

    public function default(): StatsFilterDTO
    {
        return StatsFilterDTO::fromRequest(['period' => StatsPeriod::LAST_30_DAYS->value]);
    }
}
```

---

## المرحلة 3: `StatsCacheHelper` (Cache موحّد) (~ 1 ساعة)

`Modules/Statistics/Services/StatsCacheHelper.php`:

```php
<?php

namespace Modules\Statistics\Services;

use Illuminate\Support\Facades\Cache;

class StatsCacheHelper
{
    public const CACHE_TTL = 300;

    public const CACHE_TTL_FINANCIAL = 3600;

    public const CACHE_TAGS = [
        'trader' => 'trader_stats',
        'property' => 'property_stats',
        'market' => 'market_stats',
        'admin' => 'admin_stats',
    ];

    public function remember(string $scope, string $key, array $params, int $ttl, callable $callback): mixed
    {
        $tag = self::CACHE_TAGS[$scope] ?? 'stats';
        $cacheKey = $this->makeKey($key, $params);

        return Cache::tags([$tag])->remember($cacheKey, $ttl, $callback);
    }

    public function flushScope(string $scope): void
    {
        $tag = self::CACHE_TAGS[$scope] ?? null;
        if ($tag) {
            Cache::tags([$tag])->flush();
        }
    }

    public function flushAll(): void
    {
        foreach (self::CACHE_TAGS as $tag) {
            Cache::tags([$tag])->flush();
        }
    }

    private function makeKey(string $prefix, array $params): string
    {
        ksort($params);
        return $prefix.':'.md5(json_encode($params));
    }
}
```

---

## المرحلة 4: Resource classes موحّدة (~ 1.5 ساعة)

### 4.1 `KpiCardResource`

`Modules/Statistics/Http/Resources/KpiCardResource.php`:

```php
<?php

namespace Modules\Statistics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KpiCardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'key' => $this->resource['key'] ?? null,
            'label' => $this->resource['label'] ?? null,
            'value' => $this->resource['value'] ?? 0,
            'previous_value' => $this->resource['previous_value'] ?? null,
            'change_percent' => $this->resource['change_percent'] ?? null,
            'change_direction' => $this->resource['change_direction'] ?? null,
            'format' => $this->resource['format'] ?? 'number',
            'icon' => $this->resource['icon'] ?? null,
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn($item) => (new self($item))->toArray(null), $items);
    }
}
```

### 4.2 `TimeSeriesResource`

`Modules/Statistics/Http/Resources/TimeSeriesResource.php`:

```php
<?php

namespace Modules\Statistics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimeSeriesResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'metric' => $this->resource['metric'] ?? null,
            'unit' => $this->resource['unit'] ?? 'count',
            'points' => $this->resource['points'] ?? [],
        ];
    }
}
```

### 4.3 `DistributionResource`

`Modules/Statistics/Http/Resources/DistributionResource.php`:

```php
<?php

namespace Modules\Statistics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DistributionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'dimension' => $this->resource['dimension'] ?? null,
            'items' => $this->resource['items'] ?? [],
            'total' => $this->resource['total'] ?? 0,
        ];
    }
}
```

### 4.4 `TopListResource`

`Modules/Statistics/Http/Resources/TopListResource.php`:

```php
<?php

namespace Modules\Statistics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TopListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'title' => $this->resource['title'] ?? null,
            'items' => $this->resource['items'] ?? [],
            'sort_by' => $this->resource['sort_by'] ?? null,
        ];
    }
}
```

### 4.5 `DashboardResponseBuilder` (helper)

`Modules/Statistics/Services/DashboardResponseBuilder.php`:

```php
<?php

namespace Modules\Statistics\Services;

use Modules\Statistics\DTOs\StatsFilterDTO;
use Modules\Statistics\Enums\StatsPeriod;

class DashboardResponseBuilder
{
    public static function build(
        StatsFilterDTO $filter,
        array $kpis = [],
        array $charts = [],
        array $lists = [],
        array $extra = []
    ): array {
        $payload = [
            'filter' => $filter->toArray(),
            'generated_at' => now()->toIso8601String(),
            'kpis' => $kpis,
            'charts' => $charts,
            'lists' => $lists,
        ];

        return array_merge($payload, $extra);
    }

    public static function kpi(
        string $key,
        string $label,
        int|float $value,
        ?int|float $previous = null,
        string $format = 'number',
        ?string $icon = null
    ): array {
        $changePercent = null;
        $direction = null;

        if ($previous !== null && $previous > 0) {
            $changePercent = round((($value - $previous) / $previous) * 100, 2);
            $direction = $changePercent > 0 ? 'up' : ($changePercent < 0 ? 'down' : 'flat');
        } elseif ($previous === 0 && $value > 0) {
            $changePercent = 100;
            $direction = 'up';
        }

        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'previous_value' => $previous,
            'change_percent' => $changePercent,
            'change_direction' => $direction,
            'format' => $format,
            'icon' => $icon,
        ];
    }

    public static function timeSeries(string $metric, array $points, string $unit = 'count'): array
    {
        return [
            'metric' => $metric,
            'unit' => $unit,
            'points' => $points,
        ];
    }

    public static function distribution(string $dimension, array $items, int $total = 0): array
    {
        return [
            'dimension' => $dimension,
            'items' => $items,
            'total' => $total,
        ];
    }

    public static function topList(string $title, array $items, string $sortBy = 'count'): array
    {
        return [
            'title' => $title,
            'items' => $items,
            'sort_by' => $sortBy,
        ];
    }
}
```

---

## المرحلة 5: Permissions (~ 30 دقيقة)

تحديث `database/seeders/PermissionSeeder.php` — إضافة:

```php
'statistics' => ['view', 'export'],
'admin_statistics' => ['view'],
```

**معانيها:**
- `statistics.view` — للوحة التاجر (يقرأ إحصائياته).
- `statistics.export` — تصدير CSV من لوحة التاجر.
- `admin_statistics.view` — للوحة الأدمن (يقرأ إحصائيات المنصّة).

ثم إضافة الصلاحيات لدور `trader` في `createRolesWithPermissions()`:

```php
$trader->givePermissionTo([
    // ... existing permissions
    'statistics.view',
    'statistics.export',
]);
```

> **ملاحظة:** `admin_statistics.view` تُمنح لـ `super-admin` تلقائيًا (لأن `super-admin` يحصل على كل الصلاحيات). لا حاجة لإضافتها صريحة.

---

## المرحلة 6: Stubs (Controllers + Services) (~ 1 ساعة)

في هذه المرحلة ننشئ stubs فارغة للـ 4 controllers و 4 services. كل method في الـ controllers تُرجع `501 Not Implemented` مع رسالة واضحة بأن الميزة ستُنفذ في الخطة المحددة.

### 6.1 `TraderDashboardController` stub

`Modules/Statistics/Http/Controllers/TraderDashboardController.php`:

```php
<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TraderDashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Not Implemented — see plan #26',
        ], 501);
    }

    public function viewsTrend(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function leadsByStatus(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function propertiesByStatus(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function topProperties(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function recentLeads(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function upcomingAppointments(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function expiringRentals(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function sponsoredAdsSummary(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function exportProperties()
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }
}
```

### 6.2 `PropertyStatsController` stub

`Modules/Statistics/Http/Controllers/PropertyStatsController.php`:

```php
<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RealEstate\Entities\Property;

class PropertyStatsController extends Controller
{
    public function show(int $property): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented — see plan #27'], 501);
    }
}
```

### 6.3 `MarketStatsController` stub

`Modules/Statistics/Http/Controllers/MarketStatsController.php`:

```php
<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MarketStatsController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented — see plan #27'], 501);
    }

    public function byCity(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function byCategory(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function byPriceRange(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function topViewed(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function topSaved(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function listingsTrend(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }
}
```

### 6.4 `AdminDashboardController` stub

`Modules/Statistics/Http/Controllers/AdminDashboardController.php`:

```php
<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented — see plan #28'], 501);
    }

    public function properties(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function crm(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function ads(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function subscriptions(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function moderation(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }

    public function communication(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented'], 501);
    }
}
```

### 6.5 Service stubs (فارغة)

`Modules/Statistics/Services/TraderStatsService.php`:

```php
<?php

namespace Modules\Statistics\Services;

class TraderStatsService
{
    public const CACHE_TAG = 'trader_stats';

    public const CACHE_TTL = 300;

    // Methods will be added in plan #26
}
```

`Modules/Statistics/Services/PropertyStatsService.php`:

```php
<?php

namespace Modules\Statistics\Services;

class PropertyStatsService
{
    public const CACHE_TAG = 'property_stats';

    public const CACHE_TTL = 300;

    // Methods will be added in plan #27
}
```

`Modules/Statistics/Services/MarketStatsService.php`:

```php
<?php

namespace Modules\Statistics\Services;

class MarketStatsService
{
    public const CACHE_TAG = 'market_stats';

    public const CACHE_TTL = 600;

    // Methods will be added in plan #27
}
```

`Modules/Statistics/Services/AdminStatsService.php`:

```php
<?php

namespace Modules\Statistics\Services;

class AdminStatsService
{
    public const CACHE_TAG = 'admin_stats';

    public const CACHE_TTL = 300;

    // Methods will be added in plan #28
}
```

---

## المرحلة 7: التحقق (~ 1 ساعة)

### 7.1 `FoundationTest`

`Modules/Statistics/Tests/FoundationTest.php`:

```php
<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Statistics\Enums\StatsPeriod;
use Modules\Statistics\ValueObjects\DateRange;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\PermissionSeeder']);
    }

    public function test_statistics_permissions_are_seeded(): void
    {
        $this->assertTrue(Permission::where('name', 'statistics.view')->exists());
        $this->assertTrue(Permission::where('name', 'statistics.export')->exists());
        $this->assertTrue(Permission::where('name', 'admin_statistics.view')->exists());
    }

    public function test_trader_role_has_statistics_view_and_export(): void
    {
        $trader = Role::where('name', 'trader')->first();
        $this->assertNotNull($trader);
        $this->assertTrue($trader->hasPermissionTo('statistics.view'));
        $this->assertTrue($trader->hasPermissionTo('statistics.export'));
    }

    public function test_stats_period_enum_supports_comparison(): void
    {
        $this->assertTrue(StatsPeriod::LAST_30_DAYS->supportsComparison());
        $this->assertTrue(StatsPeriod::THIS_MONTH->supportsComparison());
        $this->assertFalse(StatsPeriod::TODAY->supportsComparison());
        $this->assertFalse(StatsPeriod::CUSTOM->supportsComparison());
    }

    public function test_date_range_from_last_30_days(): void
    {
        $range = DateRange::fromPeriod(StatsPeriod::LAST_30_DAYS);
        $this->assertEquals(30, $range->days());
        $this->assertNotNull($range->previousRange());
    }

    public function test_date_range_from_today(): void
    {
        $range = DateRange::fromPeriod(StatsPeriod::TODAY);
        $this->assertEquals(1, $range->days());
    }

    public function test_trader_routes_return_501_not_implemented(): void
    {
        $trader = User::factory()->create();
        $trader->assignRole('trader');

        $response = $this->actingAs($trader)
            ->getJson('/api/dashboard/trader/summary');

        $response->assertStatus(501);
    }

    public function test_market_routes_are_public(): void
    {
        $response = $this->getJson('/api/market/overview');
        $this->assertContains($response->getStatusCode(), [200, 501]);
    }

    public function test_admin_routes_require_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/statistics/overview');

        $response->assertStatus(403);
    }
}
```

### 7.2 `composer dump-autoload`

```bash
composer dump-autoload
```

### 7.3 التحقق اليدوي

```bash
php artisan migrate                # (لا توجد جداول جديدة)
php artisan route:list --path=api  # يجب أن تظهر كل routes الإحصائيات
php artisan test --testsuite=Modules --filter=FoundationTest
```

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 + 2 | الـ ServiceProvider و Enums مستقلان | لا شيء |
| 3 + 4 | CacheHelper و Resources مستقلان | لا شيء |
| 5 | تحديث PermissionSeeder | لا شيء |
| 6 | كل الـ stubs مستقلة | لا شيء |
| 7 | بعد 1-6 | كل ما سبق |

> **ملاحظة:** لا توجد خطة أخرى في المشروع تعمل بالتوازي مع هذه (هذه الخطة هي الأساس).

---

## معايير القبول

- [ ] `Modules/Statistics/` موجودة بهيكل المجلدات الكامل.
- [ ] `StatisticsServiceProvider` يُسجَّل تلقائيًا ويُحمِّل routes.
- [ ] `StatsPeriod` enum يحتوي 10 فترات مع `label()`, `days()`, `supportsComparison()`.
- [ ] `DateRange` value object يدعم `fromPeriod()` و `custom()` و `previousRange()` و `toArray()`.
- [ ] `StatsFilterDTO` يطبّق `DTOInterface` مع `fromRequest()` و `toArray()`.
- [ ] `PeriodResolver` يحوّل Request إلى `StatsFilterDTO`.
- [ ] `StatsCacheHelper` يوفّر `remember()`, `flushScope()`, `flushAll()` مع TTL/Tags موحّدة.
- [ ] 4 Resources: `KpiCardResource`, `TimeSeriesResource`, `DistributionResource`, `TopListResource`.
- [ ] `DashboardResponseBuilder` يوفّر `kpi()`, `timeSeries()`, `distribution()`, `topList()`.
- [ ] `PermissionSeeder` يحوي `statistics.view`, `statistics.export`, `admin_statistics.view`.
- [ ] دور `trader` يحصل على `statistics.view` و `statistics.export`.
- [ ] 4 controllers و 4 services stubs موجودة وتُرجع 501.
- [ ] Routes الـ 4 مجموعات (trader, property, market, admin) ظاهرة في `php artisan route:list`.
- [ ] `composer pint` يمر بدون أخطاء.
- [ ] `php artisan test --testsuite=Modules --filter=FoundationTest` ينجح.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
