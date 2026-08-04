<?php

namespace Modules\Subscription\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Entities\User;
use Modules\Communication\Events\SubscriptionStatusChangedEvent;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionStatusLog;
use Modules\Subscription\Enums\SubscriptionStatus;

class SubscriptionService extends BaseService
{
    const CACHE_TAG = 'subscriptions';

    public function __construct(
        private readonly DiscountService $discountService
    ) {}

    public function getActiveSubscription(User $user): ?array
    {
        return (new SubscriptionAccess)->getActiveSubscription($user);
    }

    public function getSubscriptionHistory(User $user): LengthAwarePaginator
    {
        return Subscription::where('user_id', $user->id)
            ->with(['plan', 'discount', 'statusLogs'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());
    }

    public function cancel(User $user, string $reason = 'User requested cancellation'): Subscription
    {
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            throw new \RuntimeException('No active subscription to cancel.');
        }

        $previousStatus = $subscription->status->value;

        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED->value,
            'cancelled_at' => now(),
        ]);

        $this->logStatusChange(
            $subscription,
            $previousStatus,
            SubscriptionStatus::CANCELLED->value,
            $reason
        );

        $this->clearCache();

        SubscriptionStatusChangedEvent::dispatch($user, [
            'subscription_id' => $subscription->id,
            'plan_name' => $subscription->plan?->name,
            'previous_status' => $previousStatus,
            'new_status' => SubscriptionStatus::CANCELLED->value,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);

        return $subscription->fresh(['plan', 'discount']);
    }

    public function expireSubscriptions(): int
    {
        $expired = Subscription::active()
            ->where('ends_at', '<', now())
            ->with('user', 'plan')
            ->get();

        $count = 0;

        foreach ($expired as $subscription) {
            $previousStatus = $subscription->status->value;

            $subscription->update([
                'status' => SubscriptionStatus::EXPIRED->value,
            ]);

            $this->logStatusChange(
                $subscription,
                $previousStatus,
                SubscriptionStatus::EXPIRED->value,
                'Subscription expired automatically'
            );

            if ($subscription->user) {
                SubscriptionStatusChangedEvent::dispatch($subscription->user, [
                    'subscription_id' => $subscription->id,
                    'plan_name' => $subscription->plan?->name,
                    'previous_status' => $previousStatus,
                    'new_status' => SubscriptionStatus::EXPIRED->value,
                    'reason' => 'Subscription expired automatically',
                    'timestamp' => now()->toIso8601String(),
                ]);
            }

            $count++;
        }

        if ($count > 0) {
            $this->clearCache();
        }

        return $count;
    }

    public function getExpiringSubscriptions(int $daysThreshold = 3): \Illuminate\Database\Eloquent\Collection
    {
        return Subscription::active()
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays($daysThreshold)])
            ->with(['user', 'plan'])
            ->get();
    }

    public static function logStatusChange(
        Subscription $subscription,
        string $fromStatus,
        string $toStatus,
        string $reason
    ): SubscriptionStatusLog {
        return SubscriptionStatusLog::create([
            'subscription_id' => $subscription->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
        ]);
    }
}
