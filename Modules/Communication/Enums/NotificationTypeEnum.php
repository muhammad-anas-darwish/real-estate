<?php

namespace Modules\Communication\Enums;

enum NotificationTypeEnum: string
{
    case NEW_MESSAGE = 'new_message';
    case NEW_OFFER = 'new_offer';
    case PROPERTY_UPDATE = 'property_update';
    case BOOKING_CONFIRMED = 'booking_confirmed';
    case ADMIN_ALERT = 'admin_alert';

    public function label(): string
    {
        return match ($this) {
            self::NEW_MESSAGE => 'رسالة جديدة',
            self::NEW_OFFER => 'عرض جديد',
            self::PROPERTY_UPDATE => 'تحديث عقار',
            self::BOOKING_CONFIRMED => 'تأكيد الحجز',
            self::ADMIN_ALERT => 'تنبيه الإدارة',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::NEW_MESSAGE => 'New Message',
            self::NEW_OFFER => 'New Offer',
            self::PROPERTY_UPDATE => 'Property Update',
            self::BOOKING_CONFIRMED => 'Booking Confirmed',
            self::ADMIN_ALERT => 'Admin Alert',
        };
    }

    public function channels(): array
    {
        return config("communication.notification.channels.{$this->value}", ['database']);
    }
}