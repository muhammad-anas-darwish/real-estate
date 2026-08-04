<?php

namespace Modules\Subscription\Console;

use Illuminate\Console\Command;
use Modules\Communication\Notifications\SubscriptionExpiringNotification;
use Modules\Communication\Services\NotificationService;
use Modules\Subscription\Services\SubscriptionService;

class NotifyExpiringSubscriptionsCommand extends Command
{
    protected $signature = 'subscription:notify-expiring
                            {--days=3 : Number of days before expiry to notify}';

    protected $description = 'Notify users whose subscriptions are expiring soon';

    public function handle(): int
    {
        $daysThreshold = (int) $this->option('days');
        $service = app(SubscriptionService::class);
        $expiring = $service->getExpiringSubscriptions($daysThreshold);

        if ($expiring->isEmpty()) {
            $this->info('No subscriptions expiring within the threshold.');

            return self::SUCCESS;
        }

        $notificationService = app(NotificationService::class);
        $notified = 0;

        foreach ($expiring as $subscription) {
            $user = $subscription->user;

            if (! $user) {
                continue;
            }

            $daysRemaining = (int) now()->diffInDays($subscription->ends_at, false);
            $planName = $subscription->plan?->name ?? 'Unknown';

            $notification = new SubscriptionExpiringNotification(
                planName: $planName,
                daysRemaining: max($daysRemaining, 0),
                subscriptionId: $subscription->id,
            );

            $notificationService->notify($user, $notification);

            $notified++;
        }

        $this->info("Notified {$notified} user(s) about expiring subscriptions.");

        return self::SUCCESS;
    }
}
