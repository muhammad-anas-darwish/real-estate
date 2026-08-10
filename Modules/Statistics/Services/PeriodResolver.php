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
