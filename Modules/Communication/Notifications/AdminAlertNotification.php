<?php

namespace Modules\Communication\Notifications;

use Modules\Communication\Enums\NotificationTypeEnum;
use Modules\Communication\Services\Fcm\FcmPayload;
use Modules\Communication\Services\Fcm\FcmPriority;

class AdminAlertNotification extends BaseNotification
{
    public function __construct(
        public readonly string $alertTitle,
        public readonly string $alertMessage,
    ) {}

    public function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::ADMIN_ALERT;
    }

    protected function getTitle(): string
    {
        return $this->alertTitle;
    }

    protected function getBody(): string
    {
        return $this->alertMessage;
    }

    protected function toFcmData(): array
    {
        return [
            'notification_type' => $this->getNotificationType()->value,
            'alert_title' => $this->alertTitle,
            'alert_message' => $this->alertMessage,
        ];
    }

    protected function toArrayData(): array
    {
        return [
            'alert_title' => $this->alertTitle,
            'alert_message' => $this->alertMessage,
        ];
    }

    protected function getPriority(): FcmPriority
    {
        return FcmPriority::HIGH;
    }
}