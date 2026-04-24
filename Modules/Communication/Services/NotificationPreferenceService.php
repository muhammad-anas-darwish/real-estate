<?php

namespace Modules\Communication\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserNotificationPreference;

class NotificationPreferenceService
{
    private const CACHE_PREFIX = 'notification_preference:';
    private const CACHE_TTL = 3600;

    public function getEnabledChannels(User $user): array
    {
        $cached = Cache::get(self::CACHE_PREFIX . $user->id);

        if ($cached !== null) {
            return $cached;
        }

        $preferences = UserNotificationPreference::where('user_id', $user->id)
            ->where('enabled', true)
            ->pluck('channel')
            ->toArray();

        Cache::put(self::CACHE_PREFIX . $user->id, $preferences, self::CACHE_TTL);

        return $preferences;
    }

    public function updatePreference(User $user, string $channel, bool $enabled): void
    {
        UserNotificationPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'channel' => $channel,
            ],
            [
                'enabled' => $enabled,
            ]
        );

        Cache::forget(self::CACHE_PREFIX . $user->id);
    }

    public function disableAll(User $user): void
    {
        UserNotificationPreference::where('user_id', $user->id)
            ->update(['enabled' => false]);

        Cache::forget(self::CACHE_PREFIX . $user->id);
    }

    public function enableAll(User $user): void
    {
        foreach (['fcm', 'pusher', 'email'] as $channel) {
            UserNotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'channel' => $channel,
                ],
                [
                    'enabled' => true,
                ]
            );
        }

        Cache::forget(self::CACHE_PREFIX . $user->id);
    }

    public function isChannelEnabled(User $user, string $channel): bool
    {
        $enabled = $this->getEnabledChannels($user);

        return in_array($channel, $enabled);
    }

    public function clearCache(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX . $userId);
    }
}