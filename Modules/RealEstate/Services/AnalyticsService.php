<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\Review;

class AnalyticsService extends BaseService
{
    public const CACHE_TAG = 'analytics';

    public function getDashboardOverview(int $userId): array
    {
        return [
            'total_properties' => Property::where('publisher_id', $userId)->count(),
            'active_properties' => Property::where('publisher_id', $userId)
                ->whereIn('status', ['pending', 'approved'])->count(),
            'sold_properties' => Property::where('publisher_id', $userId)
                ->where('status', 'sold')->count(),
            'total_views' => (int) Property::where('publisher_id', $userId)->sum('views'),
            'favorites_count' => \DB::table('property_user')
                ->whereIn('property_id', Property::where('publisher_id', $userId)->pluck('id'))
                ->count(),
            'reviews_count' => Review::where('reviewed_id', $userId)->count(),
            'average_rating' => (float) (User::find($userId)?->average_rating ?? 0),
        ];
    }

    public function getPropertyPerformance(int $userId): array
    {
        return Property::where('publisher_id', $userId)
            ->select('id', 'name', 'status', 'views', 'price', 'currency', 'created_at')
            ->withCount(['favoritedBy as favorites_count'])
            ->withCount(['views as views_count'])
            ->orderBy('views', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function getMonthlyReport(int $userId, string $month): array
    {
        [$year, $monthNum] = explode('-', $month);

        return [
            'new_properties' => Property::where('publisher_id', $userId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->count(),
            'sold_properties' => Property::where('publisher_id', $userId)
                ->where('status', 'sold')
                ->whereYear('updated_at', $year)
                ->whereMonth('updated_at', $monthNum)
                ->count(),
            'total_views' => (int) Property::where('publisher_id', $userId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->sum('views'),
            'reviews_received' => Review::where('reviewed_id', $userId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->count(),
        ];
    }

    public function getLeadStats(int $userId): array
    {
        $propertyIds = Property::where('publisher_id', $userId)->pluck('id');

        $chatsCount = \Modules\Communication\Entities\ChatRoom::whereIn('property_id', $propertyIds)->count();

        $favoritesCount = \DB::table('property_user')
            ->whereIn('property_id', $propertyIds)
            ->count();

        return [
            'total_chats' => $chatsCount,
            'total_favorites' => $favoritesCount,
        ];
    }
}
