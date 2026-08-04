<?php

namespace Modules\Communication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Communication\Events\NotificationReceivedEvent;

abstract class BroadcastableNotification extends Notification
{
    use Queueable;

    abstract public function toBroadcastPayload($notifiable): array;

    public function via($notifiable): array
    {
        return ['database', 'pusher'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => static::class,
            'data' => $this->toBroadcastPayload($notifiable),
        ];
    }

    public function toBroadcast($notifiable): NotificationReceivedEvent
    {
        return new NotificationReceivedEvent($notifiable, $this->toBroadcastPayload($notifiable));
    }
}
