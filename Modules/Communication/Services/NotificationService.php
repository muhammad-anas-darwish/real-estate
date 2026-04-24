<?php

namespace Modules\Communication\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserFcmToken;
use Modules\Communication\Notifications\BaseNotification;

class NotificationService
{
    public function __construct(
        protected readonly NotificationPreferenceService $preferenceService,
    ) {}

    public function notify(User $user, Notification $notification): void
    {
        if (! $user->hasActiveFcmTokens()) {
            if ($notification instanceof BaseNotification) {
                $channels = $notification->getNotificationType()->channels();

                if (in_array('fcm', $channels)) {
                    $channels = array_filter($channels, fn($ch) => $ch !== 'fcm');
                }

                if (empty($channels)) {
                    Log::info('Notification skipped - no active tokens', [
                        'user_id' => $user->id,
                        'notification' => get_class($notification),
                    ]);

                    return;
                }
            }
        }

        $enabledChannels = $this->preferenceService->getEnabledChannels($user);

        $user->notify($notification);

        $this->logNotification($user, $notification, $enabledChannels);
    }

    public function notifyMany(\Illuminate\Support\Collection $users, Notification $notification): void
    {
        $users->each(fn(User $user) => $this->notify($user, $notification));
    }

    public function notifyAdmins(Notification $notification): void
    {
        $admins = \Modules\Auth\Entities\User::role('admin')
            ->whereHas('fcmTokens')
            ->get();

        $this->notifyMany($admins, $notification);
    }

    protected function logNotification(User $user, Notification $notification, array $channels): void
    {
        Log::info('Notification sent', [
            'user_id' => $user->id,
            'notification' => get_class($notification),
            'channels' => $channels,
        ]);
    }
}