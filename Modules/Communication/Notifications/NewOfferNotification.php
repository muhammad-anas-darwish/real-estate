<?php

namespace Modules\Communication\Notifications;

use Modules\Communication\Enums\NotificationTypeEnum;
use Modules\Communication\Services\Fcm\FcmPriority;

class NewOfferNotification extends BaseNotification
{
    public function __construct(
        public readonly string $propertyTitle,
        public readonly string $offerAmount,
        public readonly string $agentName,
        public readonly int $propertyId,
    ) {}

    public function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::NEW_OFFER;
    }

    protected function getTitle(): string
    {
        return 'عرض جديد على '.$this->propertyTitle;
    }

    protected function getBody(): string
    {
        return $this->agentName.' قدم عرض بـ '.$this->offerAmount;
    }

    protected function toFcmData(): array
    {
        return [
            'notification_type' => $this->getNotificationType()->value,
            'property_id' => (string) $this->propertyId,
            'property_title' => $this->propertyTitle,
            'offer_amount' => $this->offerAmount,
            'agent_name' => $this->agentName,
        ];
    }

    protected function toArrayData(): array
    {
        return [
            'property_id' => $this->propertyId,
            'property_title' => $this->propertyTitle,
            'offer_amount' => $this->offerAmount,
            'agent_name' => $this->agentName,
        ];
    }

    protected function getPriority(): FcmPriority
    {
        return FcmPriority::HIGH;
    }
}
