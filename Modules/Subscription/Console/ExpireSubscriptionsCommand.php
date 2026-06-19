<?php

namespace Modules\Subscription\Console;

use Illuminate\Console\Command;
use Modules\Subscription\Services\SubscriptionService;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscription:expire';

    protected $description = 'Mark active subscriptions as expired when their end date has passed';

    public function handle(): int
    {
        $service = app(SubscriptionService::class);
        $count = $service->expireSubscriptions();

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
