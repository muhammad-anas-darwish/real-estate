<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class AdAnalyticsResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'ad_id' => $this->id,
            'title' => $this->title,
            'total_views' => $this->total_views,
            'total_visits' => $this->total_visits,
            'ctr' => $this->ctr,
            'views_trend' => $this->views_trend ?? [],
            'visits_trend' => $this->visits_trend ?? [],
        ];
    }
}
