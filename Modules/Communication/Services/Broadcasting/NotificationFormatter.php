<?php

namespace Modules\Communication\Services\Broadcasting;

use Illuminate\Notifications\Notification;

class NotificationFormatter
{
    public function format(Notification $notification, string $channel): array
    {
        $basePayload = [
            'type' => get_class($notification),
            'channel' => $channel,
            'timestamp' => now()->toIso8601String(),
        ];

        if (method_exists($notification, 'toBroadcastPayload')) {
            return array_merge($basePayload, $notification->toBroadcastPayload(null));
        }

        return array_merge($basePayload, $notification->toArray(null));
    }

    public function formatForPusher(Notification $notification, int $userId): array
    {
        return $this->format($notification, 'user.' . $userId);
    }

    public function formatForChat(Notification $notification, int $roomId): array
    {
        return $this->format($notification, 'chat.' . $roomId);
    }

    public function formatForProperty(Notification $notification, int $propertyId): array
    {
        return $this->format($notification, 'property.' . $propertyId);
    }

    public function getNotificationType(Notification $notification): string
    {
        return (new \ReflectionClass($notification))->getShortName();
    }
}