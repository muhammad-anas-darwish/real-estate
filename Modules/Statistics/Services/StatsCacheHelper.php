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
