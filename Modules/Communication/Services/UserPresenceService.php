<?php

namespace Modules\Communication\Services;

use Illuminate\Support\Facades\Cache;

class UserPresenceService
{
    private const PRESENCE_PREFIX = 'presence:user:';

    private const ONLINE_TTL = 300;

    public function setOnline(int $userId): void
    {
        Cache::put(self::PRESENCE_PREFIX.$userId, true, self::ONLINE_TTL);
    }

    public function setOffline(int $userId): void
    {
        Cache::forget(self::PRESENCE_PREFIX.$userId);
    }

    public function isOnline(int $userId): bool
    {
        return Cache::get(self::PRESENCE_PREFIX.$userId, false);
    }

    public function getOnlineUsers(array $userIds): array
    {
        return array_filter($userIds, fn ($id) => $this->isOnline($id));
    }

    public function getOfflineUsers(array $userIds): array
    {
        return array_filter($userIds, fn ($id) => ! $this->isOnline($id));
    }

    public function refresh(int $userId): void
    {
        Cache::put(self::PRESENCE_PREFIX.$userId, true, self::ONLINE_TTL);
    }
}
