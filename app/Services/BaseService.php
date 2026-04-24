<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

abstract class BaseService
{
    /**
     * Generate a cache key based on parameters and prefix
     */
    protected function generateCacheKey(array $params, string $prefix = ''): string
    {
        $key = static::CACHE_TAG . ':' . $prefix;

        if (!empty($params)) {
            $key .= ':' . md5(json_encode($params));
        }

        return $key;
    }

    /**
     * Get per page value from request
     */
    protected function getPerPage(int $perPage = 15): int
    {
        return (int) (request('perPage') 
            ?? request('per_page') 
            ?? $perPage);
    }

    /**
     * Clear all cache for this service
     */
    protected function clearCache(): void
    {
        Cache::tags(static::CACHE_TAG)->flush();
    }
}
