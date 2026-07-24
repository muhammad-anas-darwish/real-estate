# المهمة #28: لوحة إحصائيات الأدمن (Admin Dashboard)

> **التقرير المصدر:** `docs/ideas/statistics-and-dashboards/report.md` (السطور 95–155 "لوحة الأدمن"، السيناريو 2، معايير القبول "لوحة الأدمن")
> **الهدف:** بناء لوحة إحصائيات شاملة للأدمن تغطي 7 تبويبات: Overview، عقارات، CRM، إعلانات، اشتراكات، محتوى للمراجعة، اتصال. تشمل KPIs و رسوم بيانية و جداول تفصيلية.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 عالية
> **الجهد المقدّر:** 10–14 ساعة
> **الاعتمادية:** يجب أن يسبقها #25 (Statistics Foundation).
> **راجع:** `docs/ideas/statistics-and-dashboards/report.md` (السطور 95–155، 165–170، 240–250)

---

## الوضع الحالي

لا توجد لوحة إحصائيات للأدمن. الأدمن لا يستطيع:
- رؤية إجمالي المستخدمين، العقارات، الإيرادات.
- اكتشاف طوابير المراجعة (طلبات التوثيق، طلبات ترقية الناشرين، عقارات في الانتظار).
- قياس MRR / Churn / LTV.
- اكتشاف أكثر التُجّار نشراً، أو أكثر أسباب الرفض.

هذه الخطة تنشئ:
- `AdminStatsService` مع 7 methods (overview, properties, crm, ads, subscriptions, moderation, communication).
- `AdminDashboardController` مع 7 actions.
- اختبارات شاملة.

> **ملاحظة:** الـ `AdminStatsService` و `AdminDashboardController` stubs من الخطة #25 تُملأ هنا.

---

## المرحلة 1: `AdminStatsService` (~ 5 ساعات)

`Modules/Statistics/Services/AdminStatsService.php` (يحلّ محل الـ stub):

```php
<?php

namespace Modules\Statistics\Services;

use App\Services\BaseService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\PublisherUpgradeRequest;
use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\AdVisit;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyView;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\AdType;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\Statistics\DTOs\StatsFilterDTO;

class AdminStatsService extends BaseService
{
    public const CACHE_TTL_SECONDS = 300;

    public const CACHE_TTL_FINANCIAL = 3600;

    public const CACHE_TAG = 'admin_stats';

    public function __construct(
        protected readonly StatsCacheHelper $cache
    ) {}

    public function overview(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'overview',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildOverview($filter)
        );
    }

    public function properties(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'properties',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildProperties($filter)
        );
    }

    public function crm(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'crm',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildCrm($filter)
        );
    }

    public function ads(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'ads',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_FINANCIAL,
            fn() => $this->buildAds($filter)
        );
    }

    public function subscriptions(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'subscriptions',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_FINANCIAL,
            fn() => $this->buildSubscriptions($filter)
        );
    }

    public function moderation(): array
    {
        return $this->cache->remember(
            'admin',
            'moderation',
            [],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildModeration()
        );
    }

    public function communication(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'communication',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn() => $this->buildCommunication($filter)
        );
    }

    public function clearCache(): void
    {
        $this->cache->flushScope('admin');
    }

    // ============================================
    // BUILDERS
    // ============================================

    protected function buildOverview(StatsFilterDTO $filter): array
    {
        $from = $filter->range->from;
        $to = $filter->range->to;
        $prev = $filter->previousRange;

        $totalUsers = User::whereBetween('created_at', [$from, $to])->count();
        $prevUsers = $prev ? User::whereBetween('created_at', [$prev->from, $prev->to])->count() : null;

        $traders = User::role('trader')->whereBetween('created_at', [$from, $to])->count();
        $prevTraders = $prev ? User::role('trader')->whereBetween('created_at', [$prev->from, $prev->to])->count() : null;

        $approvedProperties = Property::where('status', PropertyStatus::APPROVED)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $pendingProperties = Property::where('status', PropertyStatus::PENDING)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $pendingVerifications = PublisherUpgradeRequest::where('status', 'pending')->count();
        $pendingUpgrades = PublisherUpgradeRequest::where('status', 'pending')->count();

        $kpis = [
            DashboardResponseBuilder::kpi('total_users', 'إجمالي المستخدمين', $totalUsers, $prevUsers, icon: 'users'),
            DashboardResponseBuilder::kpi('new_traders', 'تجار جدد', $traders, $prevTraders, icon: 'briefcase'),
            DashboardResponseBuilder::kpi('approved_properties', 'عقارات معتمدة', $approvedProperties, null, icon: 'building'),
            DashboardResponseBuilder::kpi('pending_properties', 'عقارات في الانتظار', $pendingProperties, null, icon: 'clock'),
            DashboardResponseBuilder::kpi('pending_verifications', 'طلبات توثيق معلّقة', $pendingVerifications, null, icon: 'shield'),
            DashboardResponseBuilder::kpi('pending_upgrades', 'طلبات ترقية معلّقة', $pendingUpgrades, null, icon: 'arrow-up'),
        ];

        // Daily user registrations (line)
        $userRegistrations = User::whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $days = $filter->days();
        $points = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->addDays($i)->toDateString();
            $points[] = ['date' => $date, 'count' => (int) ($userRegistrations[$date] ?? 0)];
        }

        // Daily property listings
        $propertyListings = Property::whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $propertyPoints = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->addDays($i)->toDateString();
            $propertyPoints[] = ['date' => $date, 'count' => (int) ($propertyListings[$date] ?? 0)];
        }

        return [
            'filter' => $filter->toArray(),
            'kpis' => $kpis,
            'charts' => [
                DashboardResponseBuilder::timeSeries('user_registrations', $points),
                DashboardResponseBuilder::timeSeries('property_listings', $propertyPoints),
            ],
        ];
    }

    protected function buildProperties(StatsFilterDTO $filter): array
    {
        $from = $filter->range->from;
        $to = $filter->range->to;

        // Distribution by status
        $statusCounts = Property::whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->select('status', DB::raw('count(*) as count'))
            ->pluck('count', 'status');

        $statusItems = [];
        $total = 0;
        foreach (['pending', 'approved', 'rejected', 'sold'] as $status) {
            $count = (int) ($statusCounts[$status] ?? 0);
            $statusItems[] = [
                'key' => $status,
                'label' => match ($status) {
                    'pending' => 'في الانتظار',
                    'approved' => 'معتمدة',
                    'rejected' => 'مرفوضة',
                    'sold' => 'مباعة',
                },
                'count' => $count,
            ];
            $total += $count;
        }

        // Average approval time (ساعات بين created_at و approved_at)
        $approvedWithTime = Property::whereNotNull('approved_at')
            ->whereBetween('approved_at', [$from, $to])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours'))
            ->value('avg_hours');

        $avgApprovalHours = $approvedWithTime ? round((float) $approvedWithTime, 1) : 0;

        // Top 10 rejection reasons
        $rejectionReasons = Property::where('status', PropertyStatus::REJECTED)
            ->whereNotNull('rejection_reason')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('rejection_reason')
            ->select('rejection_reason', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'reason' => $r->rejection_reason,
                'count' => (int) $r->count,
            ])
            ->toArray();

        // Top 10 traders (by properties count)
        $topTraders = Property::whereBetween('properties.created_at', [$from, $to])
            ->groupBy('publisher_id')
            ->select('publisher_id', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(10)
            ->with('publisher:id,name')
            ->get()
            ->map(fn($p) => [
                'trader_id' => $p->publisher_id,
                'trader_name' => $p->publisher?->name,
                'count' => (int) $p->count,
            ])
            ->toArray();

        return [
            'kpis' => [
                DashboardResponseBuilder::kpi('avg_approval_hours', 'متوسط زمن الموافقة (ساعة)', $avgApprovalHours, null, format: 'duration', icon: 'clock'),
            ],
            'distributions' => [
                'status' => DashboardResponseBuilder::distribution('status', $statusItems, $total),
            ],
            'lists' => [
                'rejection_reasons' => DashboardResponseBuilder::topList('أكثر 10 أسباب رفض', $rejectionReasons, 'count'),
                'top_traders' => DashboardResponseBuilder::topList('أكثر 10 تجار نشراً', $topTraders, 'count'),
            ],
        ];
    }

    protected function buildCrm(StatsFilterDTO $filter): array
    {
        $from = $filter->range->from;
        $to = $filter->range->to;

        $totalLeads = Lead::whereBetween('created_at', [$from, $to])->count();
        $wonLeads = Lead::where('status', LeadStatus::WON)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 2) : 0;

        $statusCounts = Lead::whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->select('status', DB::raw('count(*) as count'))
            ->pluck('count', 'status');

        $statusItems = [];
        foreach (LeadStatus::cases() as $status) {
            $statusItems[] = [
                'key' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($statusCounts[$status->value] ?? 0),
            ];
        }

        // Average conversion time (days from new to won)
        $avgConversionDays = Lead::where('status', LeadStatus::WON)
            ->whereNotNull('status_changed_at')
            ->whereBetween('status_changed_at', [$from, $to])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(DAY, created_at, status_changed_at)) as avg_days'))
            ->value('avg_days');
        $avgConvDays = $avgConversionDays ? round((float) $avgConversionDays, 1) : 0;

        // Top 10 lead sources
        $topSources = Lead::whereBetween('created_at', [$from, $to])
            ->groupBy('source')
            ->select('source', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'source' => $s->source,
                'count' => (int) $s->count,
            ])
            ->toArray();

        return [
            'kpis' => [
                DashboardResponseBuilder::kpi('total_leads', 'إجمالي العملاء المحتملين', $totalLeads, null, icon: 'users'),
                DashboardResponseBuilder::kpi('conversion_rate', 'معدل التحويل', $conversionRate, null, format: 'percent', icon: 'target'),
                DashboardResponseBuilder::kpi('avg_conversion_days', 'متوسط زمن التحويل (أيام)', $avgConvDays, null, format: 'duration', icon: 'clock'),
            ],
            'distributions' => [
                'leads_by_status' => DashboardResponseBuilder::distribution('lead_status', $statusItems, $totalLeads),
            ],
            'lists' => [
                'top_sources' => DashboardResponseBuilder::topList('أكثر 10 مصادر', $topSources, 'count'),
            ],
        ];
    }

    protected function buildAds(StatsFilterDTO $filter): array
    {
        $from = $filter->range->from;
        $to = $filter->range->to;

        $sponsoredAds = Ad::where('type', AdType::SPONSORED)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $totalRevenue = $sponsoredAds->sum('amount_paid');
        $activeCount = $sponsoredAds->where('status', AdStatus::ACTIVE)->count();

        $adIds = $sponsoredAds->pluck('id');
        $totalViews = AdView::whereIn('ad_id', $adIds)
            ->whereBetween('viewed_at', [$from, $to])
            ->count();
        $totalVisits = AdVisit::whereIn('ad_id', $adIds)
            ->whereBetween('visited_at', [$from, $to])
            ->count();

        $cpv = $totalViews > 0 ? round((float) $totalRevenue / $totalViews, 4) : 0;

        // Top 10 spenders
        $topSpenders = $sponsoredAds->sortByDesc('amount_paid')
            ->take(10)
            ->map(fn($a) => [
                'ad_id' => $a->id,
                'title' => $a->title,
                'user_id' => $a->user_id,
                'amount_paid' => (float) $a->amount_paid,
            ])
            ->values()
            ->toArray();

        return [
            'kpis' => [
                DashboardResponseBuilder::kpi('active_sponsored', 'إعلانات نشطة', $activeCount, null, icon: 'ad'),
                DashboardResponseBuilder::kpi('total_ad_revenue', 'إيرادات الإعلانات', (float) $totalRevenue, null, format: 'currency', icon: 'money'),
                DashboardResponseBuilder::kpi('total_ad_views', 'مشاهدات الإعلانات', $totalViews, null, icon: 'eye'),
                DashboardResponseBuilder::kpi('total_ad_visits', 'زيارات الإعلانات', $totalVisits, null, icon: 'click'),
                DashboardResponseBuilder::kpi('cost_per_view', 'تكلفة المشاهدة', $cpv, null, format: 'currency', icon: 'dollar'),
            ],
            'lists' => [
                'top_spenders' => DashboardResponseBuilder::topList('أكثر 10 إعلانات إنفاقاً', $topSpenders, 'amount_paid'),
            ],
        ];
    }

    protected function buildSubscriptions(StatsFilterDTO $filter): array
    {
        $from = $filter->range->from;
        $to = $filter->range->to;

        // MRR = مجموع أسعار الباقات النشطة
        $activeSubscriptions = DB::table('subscriptions')
            ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('subscriptions.status', 'active')
            ->select('subscription_plans.price', 'subscription_plans.currency')
            ->get();

        $mrr = $activeSubscriptions->sum('price');

        // Churn rate (الشهر الحالي) = ملغى هذا الشهر / نشط بداية الشهر
        $startOfMonth = now()->startOfMonth();
        $startOfPrev = now()->subMonth()->startOfMonth();

        $cancelledThisMonth = DB::table('subscriptions')
            ->where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$startOfMonth, now()])
            ->count();

        $activeStartOfMonth = DB::table('subscriptions')
            ->where('status', 'active')
            ->where('created_at', '<', $startOfMonth)
            ->count();

        $churnRate = $activeStartOfMonth > 0
            ? round(($cancelledThisMonth / $activeStartOfMonth) * 100, 2)
            : 0;

        // Active subs by plan
        $subsByPlan = DB::table('subscriptions')
            ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('subscriptions.status', 'active')
            ->groupBy('subscription_plans.id', 'subscription_plans.name')
            ->select('subscription_plans.name', DB::raw('count(*) as count'))
            ->get()
            ->map(fn($r) => [
                'plan_name' => $r->name,
                'count' => (int) $r->count,
            ])
            ->toArray();

        // MRR trend (last 12 months)
        $mrrTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $mrrForMonth = DB::table('subscriptions')
                ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
                ->where('subscriptions.status', 'active')
                ->where('subscriptions.starts_at', '<=', $month->endOfMonth())
                ->where(function ($q) use ($month) {
                    $q->whereNull('subscriptions.ends_at')
                        ->orWhere('subscriptions.ends_at', '>=', $month->startOfMonth());
                })
                ->sum('subscription_plans.price');

            $mrrTrend[] = [
                'month' => $month->format('Y-m'),
                'value' => (float) $mrrForMonth,
            ];
        }

        return [
            'kpis' => [
                DashboardResponseBuilder::kpi('mrr', 'الإيرادات الشهرية المتكررة', (float) $mrr, null, format: 'currency', icon: 'money'),
                DashboardResponseBuilder::kpi('active_subs', 'اشتراكات نشطة', $activeSubscriptions->count(), null, icon: 'check'),
                DashboardResponseBuilder::kpi('churn_rate', 'معدل التخلّي', $churnRate, null, format: 'percent', icon: 'trending-down'),
            ],
            'charts' => [
                DashboardResponseBuilder::timeSeries('mrr', array_map(
                    fn($p) => ['date' => $p['month'].'-01', 'value' => $p['value']],
                    $mrrTrend
                ), 'currency'),
            ],
            'distributions' => [
                'by_plan' => DashboardResponseBuilder::distribution(
                    'plan',
                    $subsByPlan,
                    array_sum(array_column($subsByPlan, 'count'))
                ),
            ],
        ];
    }

    protected function buildModeration(): array
    {
        $pendingProperties = Property::where('status', PropertyStatus::PENDING)
            ->orderBy('created_at')
            ->limit(5)
            ->get(['id', 'name', 'publisher_id', 'created_at']);

        $oldestPending = Property::where('status', PropertyStatus::PENDING)
            ->orderBy('created_at')
            ->value('created_at');

        $pendingVerifications = PublisherUpgradeRequest::where('status', 'pending')
            ->orderBy('created_at')
            ->limit(5)
            ->get();

        $oldestVerification = PublisherUpgradeRequest::where('status', 'pending')
            ->orderBy('created_at')
            ->value('created_at');

        $kpis = [
            DashboardResponseBuilder::kpi('pending_properties', 'عقارات في الانتظار', Property::where('status', PropertyStatus::PENDING)->count(), null, icon: 'clock'),
            DashboardResponseBuilder::kpi('pending_verifications', 'طلبات توثيق معلّقة', PublisherUpgradeRequest::where('status', 'pending')->count(), null, icon: 'shield'),
            DashboardResponseBuilder::kpi('pending_upgrades', 'طلبات ترقية معلّقة', PublisherUpgradeRequest::where('status', 'pending')->count(), null, icon: 'arrow-up'),
        ];

        return [
            'kpis' => $kpis,
            'oldest' => [
                'pending_property_created_at' => $oldestPending?->toIso8601String(),
                'pending_verification_created_at' => $oldestVerification?->toIso8601String(),
            ],
            'lists' => [
                'pending_properties' => $pendingProperties->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'publisher_id' => $p->publisher_id,
                    'created_at' => $p->created_at?->toIso8601String(),
                ])->toArray(),
                'pending_verifications' => $pendingVerifications->map(fn($r) => [
                    'id' => $r->id,
                    'user_id' => $r->user_id,
                    'created_at' => $r->created_at?->toIso8601String(),
                ])->toArray(),
            ],
        ];
    }

    protected function buildCommunication(StatsFilterDTO $filter): array
    {
        $from = $filter->range->from;
        $to = $filter->range->to;

        $totalMessages = DB::table('messages')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $totalConversations = DB::table('conversations')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $activeConversations = DB::table('chat_rooms')
            ->where('last_message_at', '>=', now()->subDays(7))
            ->count();

        $kpis = [
            DashboardResponseBuilder::kpi('total_messages', 'إجمالي الرسائل', $totalMessages, null, icon: 'message'),
            DashboardResponseBuilder::kpi('total_conversations', 'إجمالي المحادثات', $totalConversations, null, icon: 'chat'),
            DashboardResponseBuilder::kpi('active_conversations', 'محادثات نشطة (7 أيام)', $activeConversations, null, icon: 'pulse'),
        ];

        return [
            'kpis' => $kpis,
        ];
    }
}
```

---

## المرحلة 2: `AdminDashboardController` (~ 1.5 ساعة)

`Modules/Statistics/Http/Controllers/AdminDashboardController.php` (يحلّ محل الـ stub):

```php
<?php

namespace Modules\Statistics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Statistics\Services\AdminStatsService;
use Modules\Statistics\Services\PeriodResolver;

class AdminDashboardController extends Controller
{
    use ApiResponses, ApplyPermissions, ValidatesRequests;

    public function __construct(
        protected readonly AdminStatsService $stats,
        protected readonly PeriodResolver $periodResolver
    ) {
        $this->applyPermissions(
            'admin_statistics',
            [],
            [
                'overview' => 'view',
                'properties' => 'view',
                'crm' => 'view',
                'ads' => 'view',
                'subscriptions' => 'view',
                'moderation' => 'view',
                'communication' => 'view',
            ]
        );
    }

    public function overview(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->overview($this->periodResolver->fromRequest($request))
        );
    }

    public function properties(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->properties($this->periodResolver->fromRequest($request))
        );
    }

    public function crm(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->crm($this->periodResolver->fromRequest($request))
        );
    }

    public function ads(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->ads($this->periodResolver->fromRequest($request))
        );
    }

    public function subscriptions(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->subscriptions($this->periodResolver->fromRequest($request))
        );
    }

    public function moderation(): JsonResponse
    {
        return $this->successResponse($this->stats->moderation());
    }

    public function communication(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->stats->communication($this->periodResolver->fromRequest($request))
        );
    }
}
```

---

## المرحلة 3: اختبارات (~ 4 ساعات)

`Modules/Statistics/Tests/AdminDashboardTest.php`:

```php
<?php

namespace Modules\Statistics\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\PublisherUpgradeRequest;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\AdVisit;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\AdType;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionPlan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'admin_statistics.view', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    // ============================================
    // OVERVIEW
    // ============================================

    public function test_overview_returns_six_kpis(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/overview?period=last_30_days');

        $response->assertStatus(200);
        $this->assertCount(6, $response->json('data.kpis'));
    }

    public function test_overview_counts_new_users(): void
    {
        User::factory()->count(5)->create(['created_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/overview?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(5, $kpis->firstWhere('key', 'total_users')['value']);
    }

    public function test_overview_includes_registration_trend(): void
    {
        User::factory()->create(['created_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/overview?period=last_7_days');

        $charts = $response->json('data.charts');
        $this->assertGreaterThanOrEqual(1, count($charts));
        $this->assertEquals('user_registrations', $charts[0]['metric']);
    }

    // ============================================
    // PROPERTIES
    // ============================================

    public function test_properties_status_distribution(): void
    {
        Property::factory()->count(3)->create(['status' => PropertyStatus::APPROVED]);
        Property::factory()->count(2)->create(['status' => PropertyStatus::PENDING]);
        Property::factory()->create(['status' => PropertyStatus::REJECTED, 'rejection_reason' => 'صور سيئة']);
        Property::factory()->create(['status' => PropertyStatus::REJECTED, 'rejection_reason' => 'صور سيئة']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/properties?period=last_30_days');

        $response->assertStatus(200);
        $items = collect($response->json('data.distributions.status.items'));
        $this->assertEquals(3, $items->firstWhere('key', 'approved')['count']);
        $this->assertEquals(2, $items->firstWhere('key', 'pending')['count']);
    }

    public function test_properties_top_rejection_reasons(): void
    {
        Property::factory()->count(3)->create(['status' => PropertyStatus::REJECTED, 'rejection_reason' => 'صور سيئة']);
        Property::factory()->count(2)->create(['status' => PropertyStatus::REJECTED, 'rejection_reason' => 'سعر غير واقعي']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/properties?period=last_30_days');

        $reasons = $response->json('data.lists.rejection_reasons.items');
        $this->assertEquals('صور سيئة', $reasons[0]['reason']);
        $this->assertEquals(3, $reasons[0]['count']);
    }

    // ============================================
    // CRM
    // ============================================

    public function test_crm_leads_distribution(): void
    {
        Lead::factory()->count(3)->create(['status' => LeadStatus::NEW]);
        Lead::factory()->count(2)->create(['status' => LeadStatus::WON]);
        Lead::factory()->create(['status' => LeadStatus::LOST]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/crm?period=last_30_days');

        $response->assertStatus(200);
        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(6, $kpis->firstWhere('key', 'total_leads')['value']);
        $this->assertGreaterThan(0, $kpis->firstWhere('key', 'conversion_rate')['value']);
    }

    public function test_crm_top_lead_sources(): void
    {
        Lead::factory()->count(5)->create(['source' => LeadSource::WEBSITE]);
        Lead::factory()->count(3)->create(['source' => LeadSource::WHATSAPP]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/crm?period=last_30_days');

        $sources = $response->json('data.lists.top_sources.items');
        $this->assertEquals('website', $sources[0]['source']);
        $this->assertEquals(5, $sources[0]['count']);
    }

    // ============================================
    // ADS
    // ============================================

    public function test_ads_calculates_revenue(): void
    {
        Ad::factory()->create([
            'type' => AdType::SPONSORED,
            'status' => AdStatus::ACTIVE,
            'amount_paid' => 100,
        ]);
        Ad::factory()->create([
            'type' => AdType::SPONSORED,
            'status' => AdStatus::ACTIVE,
            'amount_paid' => 200,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/ads?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(2, $kpis->firstWhere('key', 'active_sponsored')['value']);
        $this->assertEquals(300, $kpis->firstWhere('key', 'total_ad_revenue')['value']);
    }

    // ============================================
    // SUBSCRIPTIONS
    // ============================================

    public function test_subscriptions_mrr_calculation(): void
    {
        $plan = SubscriptionPlan::factory()->create(['price' => 100, 'is_active' => true]);
        Subscription::factory()->count(3)->create([
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/subscriptions?period=last_30_days');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(300, $kpis->firstWhere('key', 'mrr')['value']);
        $this->assertEquals(3, $kpis->firstWhere('key', 'active_subs')['value']);
    }

    public function test_subscriptions_mrr_trend_chart(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/subscriptions?period=last_30_days');

        $charts = $response->json('data.charts');
        $mrrChart = collect($charts)->firstWhere('metric', 'mrr');
        $this->assertNotNull($mrrChart);
        $this->assertEquals(12, count($mrrChart['points']));
    }

    // ============================================
    // MODERATION
    // ============================================

    public function test_moderation_counts_pending_items(): void
    {
        Property::factory()->count(3)->create(['status' => PropertyStatus::PENDING]);
        PublisherUpgradeRequest::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/statistics/moderation');

        $kpis = collect($response->json('data.kpis'));
        $this->assertEquals(3, $kpis->firstWhere('key', 'pending_properties')['value']);
        $this->assertEquals(2, $kpis->firstWhere('key', 'pending_verifications')['value']);
    }

    public function test_moderation_returns_oldest_pending(): void
    {
        $oldest = Property::factory()->create([
            'status' => PropertyStatus::PENDING,
            'created_at' => now()->subDays(5),
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::PENDING,
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/statistics/moderation');

        $this->assertNotNull($response->json('data.oldest.pending_property_created_at'));
    }

    // ============================================
    // COMMUNICATION
    // ============================================

    public function test_communication_kpis(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistics/communication?period=last_30_days');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($response->json('data.kpis')));
    }

    // ============================================
    // AUTHORIZATION
    // ============================================

    public function test_admin_endpoint_requires_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/statistics/overview');

        $response->assertStatus(403);
    }

    public function test_admin_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/admin/statistics/overview');
        $response->assertStatus(401);
    }

    public function test_super_admin_can_access_all_tabs(): void
    {
        $endpoints = [
            'overview', 'properties', 'crm', 'ads', 'subscriptions', 'moderation', 'communication',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($this->admin)
                ->getJson("/api/admin/statistics/{$endpoint}?period=last_30_days");

            $response->assertStatus(200, "Failed for endpoint: {$endpoint}");
        }
    }
}
```

> **ملاحظة:** إذا لم تكن `Subscription::factory()` أو `SubscriptionPlan::factory()` موجودة، تُضاف minimal factory states.

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Service) | مستقل | #25 |
| 2 (Controller) | يعتمد على 1 | #25 + 1 |
| 3 (Tests) | يعتمد على 1 + 2 | #25 + 1 + 2 |

> **ملاحظة:** هذه الخطة تعمل بالتوازي الكامل مع #26 (لوحة التاجر) و #27 (إحصائيات العقار والسوق).

---

## معايير القبول

- [ ] `AdminStatsService` يحوي 7 methods (overview, properties, crm, ads, subscriptions, moderation, communication).
- [ ] `AdminDashboardController` يحوي 7 actions مع `applyPermissions('admin_statistics', [], [...])`.
- [ ] `GET /api/admin/statistics/overview?period=last_30_days` يُعيد 6 KPIs + 2 charts (user_registrations, property_listings).
- [ ] `GET /api/admin/statistics/properties` يُعيد distribution بالحالات + قائمة أكثر 10 أسباب رفض + أكثر 10 تجار نشراً.
- [ ] `GET /api/admin/statistics/crm` يُعيد conversion_rate + avg_conversion_days + leads_by_status + top_sources.
- [ ] `GET /api/admin/statistics/ads` يُعيد active_sponsored + total_ad_revenue + cost_per_view + top_spenders.
- [ ] `GET /api/admin/statistics/subscriptions` يُعيد MRR + active_subs + churn_rate + by_plan distribution + mrr trend (12 شهر).
- [ ] `GET /api/admin/statistics/moderation` يُعيد 3 KPIs + oldest pending + lists.
- [ ] `GET /api/admin/statistics/communication` يُعيد 3 KPIs (total_messages, total_conversations, active_conversations).
- [ ] مستخدم بغير صلاحية `admin_statistics.view` يحصل على 403.
- [ ] super-admin يصل لكل الـ 7 endpoints.
- [ ] Cache يعمل: 5 دقائق للـ overview/properties/crm/moderation/communication، ساعة للـ ads/subscriptions.
- [ ] `php artisan test --testsuite=Modules --filter=AdminDashboardTest` ينجح.
- [ ] `composer pint` يمر بدون أخطاء.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
