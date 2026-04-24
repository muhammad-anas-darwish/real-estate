<?php

namespace Modules\Communication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Communication\Services\Fcm\FcmPayload;

abstract class FcmNotification extends Notification
{
    use Queueable;

    abstract public function toFcm($notifiable): FcmPayload;

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => static::class,
            'data' => $this->toFcmData($notifiable),
        ];
    }

    protected function toFcmData($notifiable): array
    {
        return [];
    }
}