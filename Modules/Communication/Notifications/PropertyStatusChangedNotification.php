<?php

namespace Modules\Communication\Notifications;

use Modules\Communication\Enums\NotificationTypeEnum;
use Modules\Communication\Services\Fcm\FcmPriority;

class PropertyStatusChangedNotification extends BaseNotification
{
    public function __construct(
        public readonly string $propertyTitle,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly int $propertyId,
        public readonly ?string $rejectionReason = null,
    ) {}

    public function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::PROPERTY_UPDATE;
    }

    protected function getTitle(): string
    {
        if ($this->newStatus === 'rejected') {
            return 'Property Rejected';
        }

        if ($this->newStatus === 'approved') {
            return 'Property Approved';
        }

        return 'Property Status Updated';
    }

    protected function getBody(): string
    {
        $body = $this->propertyTitle.': '.$this->oldStatus.' → '.$this->newStatus;

        if ($this->rejectionReason) {
            $body .= '. Reason: '.$this->rejectionReason;
        }

        return $body;
    }

    protected function toFcmData(): array
    {
        return [
            'notification_type' => $this->getNotificationType()->value,
            'property_id' => (string) $this->propertyId,
            'property_title' => $this->propertyTitle,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'rejection_reason' => $this->rejectionReason,
        ];
    }

    protected function toArrayData(): array
    {
        return [
            'property_id' => $this->propertyId,
            'property_title' => $this->propertyTitle,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'rejection_reason' => $this->rejectionReason,
        ];
    }

    protected function getPriority(): FcmPriority
    {
        return FcmPriority::NORMAL;
    }
}
