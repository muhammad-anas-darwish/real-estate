<?php

namespace Modules\Communication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Communication\Enums\NotificationTypeEnum;
use Modules\Communication\Services\Fcm\FcmPayload;

abstract class BaseNotification extends Notification
{
    use Queueable;

    abstract public function getNotificationType(): NotificationTypeEnum;

    public function via($notifiable): array
    {
        $channels = $this->getNotificationType()->channels();

        $via = [];
        if (in_array('database', $channels)) {
            $via[] = 'database';
        }
        if (in_array('pusher', $channels)) {
            $via[] = 'pusher';
        }
        if (in_array('fcm', $channels)) {
            $via[] = 'fcm';
        }

        return $via;
    }

    public function toFcm($notifiable): FcmPayload
    {
        return new FcmPayload(
            title: $this->getTitle(),
            body: $this->getBody(),
            data: $this->toFcmData(),
            priority: $this->getPriority(),
        );
    }

    public function toBroadcastPayload($notifiable): array
    {
        return [
            'type' => $this->getNotificationType()->value,
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'data' => $this->toArrayData(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type' => $this->getNotificationType()->value,
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'data' => $this->toArrayData(),
        ];
    }

    abstract protected function getTitle(): string;

    abstract protected function getBody(): string;

    abstract protected function toFcmData(): array;

    abstract protected function toArrayData(): array;

    abstract protected function getPriority(): \Modules\Communication\Services\Fcm\FcmPriority;
}
