<?php

namespace Modules\Crm\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Entities\Lead;
use Modules\RealEstate\Entities\Appointment;

class LeadStatsService extends BaseService
{
    protected const CACHE_TTL_SECONDS = 300;

    protected const CACHE_TAG = 'crm_stats';

    public function summary(int $traderId): array
    {
        $cacheKey = $this->generateCacheKey(['trader' => $traderId], 'summary');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($traderId) {
            return [
                'new_leads_this_week' => Lead::where('trader_id', $traderId)
                    ->where('created_at', '>=', now()->subWeek())
                    ->count(),
                'today_follow_ups' => Appointment::where('agent_id', $traderId)
                    ->where('type', 'follow_up')
                    ->whereDate('scheduled_at', today())
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->count(),
                'overdue_follow_ups' => Appointment::where('agent_id', $traderId)
                    ->where('type', 'follow_up')
                    ->where('scheduled_at', '<', now())
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->count(),
                'leads_by_status' => Lead::where('trader_id', $traderId)
                    ->active()
                    ->groupBy('status')
                    ->select('status', DB::raw('count(*) as count'))
                    ->pluck('count', 'status'),
            ];
        });
    }

    public function today(int $traderId): array
    {
        $cacheKey = $this->generateCacheKey([
            'trader' => $traderId,
            'date' => today()->toDateString(),
        ], 'today');

        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($traderId) {
            $todayAppointments = Appointment::where('agent_id', $traderId)
                ->where('type', 'follow_up')
                ->whereDate('scheduled_at', today())
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->with('followable')
                ->orderBy('scheduled_at')
                ->get();

            $overdueAppointments = Appointment::where('agent_id', $traderId)
                ->where('type', 'follow_up')
                ->where('scheduled_at', '<', now())
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->with('followable')
                ->orderBy('scheduled_at')
                ->get();

            return [
                'today_appointments' => $todayAppointments,
                'overdue_appointments' => $overdueAppointments,
            ];
        });
    }

    public function clearCache(): void
    {
        Cache::tags(self::CACHE_TAG)->flush();
    }
}
