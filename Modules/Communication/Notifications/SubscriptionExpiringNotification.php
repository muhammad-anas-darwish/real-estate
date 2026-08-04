<?php

namespace Modules\Communication\Notifications;

use Modules\Communication\Enums\NotificationTypeEnum;
use Modules\Communication\Services\Fcm\FcmPriority;

class SubscriptionExpiringNotification extends BaseNotification
{
    public function __construct(
        public readonly string $planName,
        public readonly int $daysRemaining,
        public readonly int $subscriptionId,
    ) {}

    public function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::SUBSCRIPTION_EXPIRING;
    }

    protected function getTitle(): string
    {
        return 'Subscription Expiring Soon';
    }

    protected function getBody(): string
    {
        $dayLabel = $this->daysRemaining === 1 ? 'day' : 'days';

        return "Your {$this->planName} plan expires in {$this->daysRemaining} {$dayLabel}. Renew now to keep enjoying all features.";
    }

    protected function toFcmData(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'plan_name' => $this->planName,
            'days_remaining' => $this->daysRemaining,
            'type' => 'subscription_expiring',
        ];
    }

    protected function toArrayData(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'plan_name' => $this->planName,
            'days_remaining' => $this->daysRemaining,
        ];
    }

    protected function getPriority(): FcmPriority
    {
        return FcmPriority::NORMAL;
    }
}
