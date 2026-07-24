<?php

namespace Modules\Statistics\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\PublisherUpgradeRequest;
use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\AdVisit;
use Modules\RealEstate\Entities\Property;
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
            fn () => $this->buildOverview($filter)
        );
    }

    public function properties(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'properties',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildProperties($filter)
        );
    }

    public function crm(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'crm',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildCrm($filter)
        );
    }

    public function ads(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'ads',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_FINANCIAL,
            fn () => $this->buildAds($filter)
        );
    }

    public function subscriptions(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'subscriptions',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_FINANCIAL,
            fn () => $this->buildSubscriptions($filter)
        );
    }

    public function moderation(): array
    {
        return $this->cache->remember(
            'admin',
            'moderation',
            [],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildModeration()
        );
    }

    public function communication(StatsFilterDTO $filter): array
    {
        return $this->cache->remember(
            'admin',
            'communication',
            ['f' => $filter->toArray()],
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildCommunication($filter)
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

        $kpis = [
            DashboardResponseBuilder::kpi('total_users', 'إجمالي المستخدمين', $totalUsers, $prevUsers, icon: 'users'),
            DashboardResponseBuilder::kpi('new_traders', 'تجار جدد', $traders, $prevTraders, icon: 'briefcase'),
            DashboardResponseBuilder::kpi('approved_properties', 'عقارات معتمدة', $approvedProperties, null, icon: 'building'),
            DashboardResponseBuilder::kpi('pending_properties', 'عقارات في الانتظار', $pendingProperties, null, icon: 'clock'),
            DashboardResponseBuilder::kpi('pending_verifications', 'طلبات توثيق معلّقة', $pendingVerifications, null, icon: 'shield'),
        ];

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
            'generated_at' => now()->toIso8601String(),
            'kpis' => $kpis,
            'charts' => [
                [
                    'metric' => 'user_registrations',
                    'unit' => 'count',
                    'points' => $points,
                ],
                [
                    'metric' => 'property_listings',
                    'unit' => 'count',
                    'points' => $propertyPoints,
                ],
            ],
        ];
    }

    protected function buildProperties(StatsFilterDTO $filter): array
    {
        $from = $filter->range->from;
        $to = $filter->range->to;

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

        $approvedWithTime = Property::whereNotNull('approved_at')
            ->whereBetween('approved_at', [$from, $to])
            ->select(DB::raw('AVG((JULIANDAY(approved_at) - JULIANDAY(created_at)) * 24) as avg_hours'))
            ->value('avg_hours');

        $avgApprovalHours = $approvedWithTime ? round((float) $approvedWithTime, 1) : 0;

        $rejectionReasons = Property::where('status', PropertyStatus::REJECTED)
            ->whereNotNull('rejection_reason')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('rejection_reason')
            ->select('rejection_reason', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'reason' => $r->rejection_reason,
                'count' => (int) $r->count,
            ])
            ->toArray();

        $topTraders = Property::whereBetween('properties.created_at', [$from, $to])
            ->groupBy('publisher_id')
            ->select('publisher_id', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(10)
            ->with('publisher:id,name')
            ->get()
            ->map(fn ($p) => [
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
                'status' => [
                    'dimension' => 'status',
                    'items' => $statusItems,
                    'total' => $total,
                ],
            ],
            'lists' => [
                'rejection_reasons' => [
                    'title' => 'أكثر 10 أسباب رفض',
                    'items' => $rejectionReasons,
                    'sort_by' => 'count',
                ],
                'top_traders' => [
                    'title' => 'أكثر 10 تجار نشراً',
                    'items' => $topTraders,
                    'sort_by' => 'count',
                ],
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

        $avgConversionDays = Lead::where('status', LeadStatus::WON)
            ->whereNotNull('status_changed_at')
            ->whereBetween('status_changed_at', [$from, $to])
            ->select(DB::raw('AVG(JULIANDAY(status_changed_at) - JULIANDAY(created_at)) as avg_days'))
            ->value('avg_days');
        $avgConvDays = $avgConversionDays ? round((float) $avgConversionDays, 1) : 0;

        $topSources = Lead::whereBetween('created_at', [$from, $to])
            ->groupBy('source')
            ->select('source', DB::raw('count(*) as count'))
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
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
                'leads_by_status' => [
                    'dimension' => 'lead_status',
                    'items' => $statusItems,
                    'total' => $totalLeads,
                ],
            ],
            'lists' => [
                'top_sources' => [
                    'title' => 'أكثر 10 مصادر',
                    'items' => $topSources,
                    'sort_by' => 'count',
                ],
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

        $totalRevenue = (float) $sponsoredAds->sum('amount_paid');
        $activeCount = $sponsoredAds->where('status', AdStatus::ACTIVE)->count();

        $adIds = $sponsoredAds->pluck('id');
        $totalViews = AdView::whereIn('ad_id', $adIds)
            ->whereBetween('viewed_at', [$from, $to])
            ->count();
        $totalVisits = AdVisit::whereIn('ad_id', $adIds)
            ->whereBetween('visited_at', [$from, $to])
            ->count();

        $cpv = $totalViews > 0 ? round($totalRevenue / $totalViews, 4) : 0;

        $topSpenders = $sponsoredAds->sortByDesc('amount_paid')
            ->take(10)
            ->map(fn ($a) => [
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
                DashboardResponseBuilder::kpi('total_ad_revenue', 'إيرادات الإعلانات', $totalRevenue, null, format: 'currency', icon: 'money'),
                DashboardResponseBuilder::kpi('total_ad_views', 'مشاهدات الإعلانات', $totalViews, null, icon: 'eye'),
                DashboardResponseBuilder::kpi('total_ad_visits', 'زيارات الإعلانات', $totalVisits, null, icon: 'click'),
                DashboardResponseBuilder::kpi('cost_per_view', 'تكلفة المشاهدة', $cpv, null, format: 'currency', icon: 'dollar'),
            ],
            'lists' => [
                'top_spenders' => [
                    'title' => 'أكثر 10 إعلانات إنفاقاً',
                    'items' => $topSpenders,
                    'sort_by' => 'amount_paid',
                ],
            ],
        ];
    }

    protected function buildSubscriptions(StatsFilterDTO $filter): array
    {
        $activeSubscriptions = DB::table('subscriptions')
            ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('subscriptions.status', 'active')
            ->select('subscription_plans.price', 'subscription_plans.currency')
            ->get();

        $mrr = (float) $activeSubscriptions->sum('price');

        $startOfMonth = now()->startOfMonth();

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

        $subsByPlan = DB::table('subscriptions')
            ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('subscriptions.status', 'active')
            ->groupBy('subscription_plans.id', 'subscription_plans.name')
            ->select('subscription_plans.name', DB::raw('count(*) as count'))
            ->get()
            ->map(fn ($r) => [
                'plan_name' => $r->name,
                'count' => (int) $r->count,
            ])
            ->toArray();

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
                'date' => $month->format('Y-m').'-01',
                'value' => (float) $mrrForMonth,
            ];
        }

        return [
            'kpis' => [
                DashboardResponseBuilder::kpi('mrr', 'الإيرادات الشهرية المتكررة', $mrr, null, format: 'currency', icon: 'money'),
                DashboardResponseBuilder::kpi('active_subs', 'اشتراكات نشطة', $activeSubscriptions->count(), null, icon: 'check'),
                DashboardResponseBuilder::kpi('churn_rate', 'معدل التخلّي', $churnRate, null, format: 'percent', icon: 'trending-down'),
            ],
            'charts' => [
                [
                    'metric' => 'mrr',
                    'unit' => 'currency',
                    'points' => $mrrTrend,
                ],
            ],
            'distributions' => [
                'by_plan' => [
                    'dimension' => 'plan',
                    'items' => $subsByPlan,
                    'total' => array_sum(array_column($subsByPlan, 'count')),
                ],
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
                'pending_properties' => $pendingProperties->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'publisher_id' => $p->publisher_id,
                    'created_at' => $p->created_at?->toIso8601String(),
                ])->toArray(),
                'pending_verifications' => $pendingVerifications->map(fn ($r) => [
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
