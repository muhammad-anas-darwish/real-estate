<?php

namespace Modules\Statistics\Services;

use Modules\Statistics\DTOs\StatsFilterDTO;

class DashboardResponseBuilder
{
    public static function build(
        StatsFilterDTO $filter,
        array $kpis = [],
        array $charts = [],
        array $lists = [],
        array $distributions = [],
        array $extra = []
    ): array {
        $payload = [
            'filter' => $filter->toArray(),
            'generated_at' => now()->toIso8601String(),
            'kpis' => $kpis,
            'charts' => $charts,
            'distributions' => $distributions,
            'lists' => $lists,
        ];

        return array_merge($payload, $extra);
    }

    public static function kpi(
        string $key,
        string $label,
        int|float $value,
        int|float|null $previous = null,
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
