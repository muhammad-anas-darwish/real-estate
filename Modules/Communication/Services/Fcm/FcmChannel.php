<?php

namespace Modules\Communication\Services\Fcm;

use Illuminate\Notifications\Channels\Channel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Entities\User;

class FcmChannel extends Channel
{
    public function __construct(
        protected readonly FcmService $fcmService,
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            return;
        }

        $tokens = $notifiable->routeNotificationForFcm();

        if ($tokens->isEmpty()) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        if (! $payload instanceof FcmPayload) {
            Log::warning('FcmNotification::toFcm() did not return FcmPayload', [
                'notifiable' => get_class($notifiable),
                'notification' => get_class($notification),
            ]);

            return;
        }

        $result = $this->fcmService->sendToTokens($tokens->toArray(), $payload);

        if ($result->hasFailures()) {
            Log::warning('FCM notification failed', [
                'user_id' => $notifiable->id,
                'notification' => get_class($notification),
                'failures' => $result->failureCount,
                'failed_tokens' => $result->failedTokens,
            ]);
        }
    }

    public function sends(): string
    {
        return 'fcm';
    }
}