<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class DashboardAnalyticsResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'total_views' => $this->total_views,
            'total_visits' => $this->total_visits,
            'avg_ctr' => $this->avg_ctr,
            'top_performing_ads' => $this->top_performing_ads ?? [],
            'views_by_day' => $this->views_by_day ?? [],
            'visits_by_day' => $this->visits_by_day ?? [],
        ];
    }
}
