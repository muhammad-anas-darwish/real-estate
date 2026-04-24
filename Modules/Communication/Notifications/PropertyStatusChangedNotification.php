<?php

namespace Modules\Communication\Notifications;

use Modules\Communication\Enums\NotificationTypeEnum;
use Modules\Communication\Services\Fcm\FcmPayload;
use Modules\Communication\Services\Fcm\FcmPriority;

class PropertyStatusChangedNotification extends BaseNotification
{
    public function __construct(
        public readonly string $propertyTitle,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly int $propertyId,
    ) {}

    public function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::PROPERTY_UPDATE;
    }

    protected function getTitle(): string
    {
        return 'تحديث حالة العقار';
    }

    protected function getBody(): string
    {
        return $this->propertyTitle . ': ' . $this->oldStatus . ' → ' . $this->newStatus;
    }

    protected function toFcmData(): array
    {
        return [
            'notification_type' => $this->getNotificationType()->value,
            'property_id' => (string) $this->propertyId,
            'property_title' => $this->propertyTitle,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }

    protected function toArrayData(): array
    {
        return [
            'property_id' => $this->propertyId,
            'property_title' => $this->propertyTitle,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }

    protected function getPriority(): FcmPriority
    {
        return FcmPriority::NORMAL;
    }
}