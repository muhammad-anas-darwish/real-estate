<?php

namespace Modules\Communication\Notifications;

use Modules\Communication\Enums\NotificationTypeEnum;
use Modules\Communication\Services\Fcm\FcmPayload;
use Modules\Communication\Services\Fcm\FcmPriority;

class BookingConfirmedNotification extends BaseNotification
{
    public function __construct(
        public readonly string $propertyTitle,
        public readonly string $bookingDate,
        public readonly string $bookingReference,
        public readonly int $propertyId,
    ) {}

    public function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::BOOKING_CONFIRMED;
    }

    protected function getTitle(): string
    {
        return 'تم تأكيد حجزك';
    }

    protected function getBody(): string
    {
        return 'تم تأكيد حجزك للعقار ' . $this->propertyTitle . ' في ' . $this->bookingDate;
    }

    protected function toFcmData(): array
    {
        return [
            'notification_type' => $this->getNotificationType()->value,
            'property_id' => (string) $this->propertyId,
            'property_title' => $this->propertyTitle,
            'booking_date' => $this->bookingDate,
            'booking_reference' => $this->bookingReference,
        ];
    }

    protected function toArrayData(): array
    {
        return [
            'property_id' => $this->propertyId,
            'property_title' => $this->propertyTitle,
            'booking_date' => $this->bookingDate,
            'booking_reference' => $this->bookingReference,
        ];
    }

    protected function getPriority(): FcmPriority
    {
        return FcmPriority::HIGH;
    }
}