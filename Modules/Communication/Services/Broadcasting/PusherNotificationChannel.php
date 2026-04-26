<?php

namespace Modules\Communication\Services\Broadcasting;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Entities\User;

class PusherNotificationChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            return;
        }

        $eventClass = get_class($notification);
        $channel = 'user.' . $notifiable->id;

        if (method_exists($notification, 'toBroadcastPayload')) {
            $payload = $notification->toBroadcastPayload($notifiable);
        } else {
            $payload = $notification->toArray($notifiable);
        }

        $payload['notification_type'] = $eventClass;
        $payload['channel'] = $channel;
        $payload['timestamp'] = now()->toIso8601String();

        Event::dispatch(new NotificationReceivedEvent($notifiable, $payload));
    }

    public function sendViaMiddleware(): array
    {
        return ['socket'];
    }
}