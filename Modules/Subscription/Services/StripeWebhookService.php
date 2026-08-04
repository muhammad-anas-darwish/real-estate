<?php

namespace Modules\Subscription\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Entities\User;
use Modules\Communication\Events\SubscriptionStatusChangedEvent;
use Modules\Subscription\Entities\StripeEvent;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionDiscount;
use Modules\Subscription\Entities\SubscriptionPlan;
use Modules\Subscription\Enums\SubscriptionStatus;

class StripeWebhookService
{
    public function __construct(
        private readonly DiscountService $discountService
    ) {}

    public function processEvent(string $eventId, string $eventType, array $payload): void
    {
        if (StripeEvent::where('stripe_event_id', $eventId)->where('status', 'processed')->exists()) {
            Log::info('Stripe webhook: event already processed', ['event_id' => $eventId]);

            return;
        }

        $stripeEvent = StripeEvent::updateOrCreate(
            ['stripe_event_id' => $eventId],
            [
                'type' => $eventType,
                'status' => 'processing',
                'payload' => $payload,
            ]
        );

        try {
            DB::transaction(function () use ($eventType, $payload) {
                match ($eventType) {
                    'checkout.session.completed' => $this->handleCheckoutCompleted($payload),
                    'checkout.session.expired' => $this->handleCheckoutExpired($payload),
                    'charge.refunded' => $this->handleChargeRefunded($payload),
                    default => Log::info('Stripe webhook: unhandled event type', ['type' => $eventType]),
                };
            });

            $stripeEvent->update(['status' => 'processed']);
        } catch (\Exception $e) {
            $stripeEvent->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Stripe webhook: processing failed', [
                'event_id' => $stripeEvent->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function handleCheckoutCompleted(array $payload): void
    {
        $sessionId = data_get($payload, 'data.object.id');
        $metadata = data_get($payload, 'data.object.metadata', []);

        $subscription = Subscription::where('stripe_checkout_session_id', $sessionId)->first();

        if (! $subscription) {
            Log::warning('Stripe webhook: no subscription found for session', ['session_id' => $sessionId]);

            return;
        }

        if ($subscription->status === SubscriptionStatus::ACTIVE) {
            Log::info('Stripe webhook: subscription already active', ['subscription_id' => $subscription->id]);

            return;
        }

        $plan = SubscriptionPlan::find($subscription->plan_id);
        if (! $plan) {
            Log::error('Stripe webhook: plan not found', ['plan_id' => $subscription->plan_id]);

            return;
        }

        $previousStatus = $subscription->status->value;
        $now = now();
        $endsAt = $now->copy()->addDays($plan->duration_days);

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => $now,
            'ends_at' => $endsAt,
        ]);

        $this->logStatusChange($subscription, $previousStatus, SubscriptionStatus::ACTIVE->value, 'Payment completed via Stripe');

        if ($subscription->discount_id) {
            $discount = SubscriptionDiscount::find($subscription->discount_id);
            if ($discount) {
                $this->discountService->incrementUsage($discount);
            }
        }

        $user = User::find($subscription->user_id);
        if ($user) {
            SubscriptionStatusChangedEvent::dispatch($user, [
                'subscription_id' => $subscription->id,
                'plan_name' => $plan->name,
                'previous_status' => $previousStatus,
                'new_status' => SubscriptionStatus::ACTIVE->value,
                'reason' => 'Payment completed via Stripe',
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        Log::info('Stripe webhook: subscription activated', ['subscription_id' => $subscription->id]);
    }

    private function handleCheckoutExpired(array $payload): void
    {
        $sessionId = data_get($payload, 'data.object.id');

        $subscription = Subscription::where('stripe_checkout_session_id', $sessionId)->first();

        if (! $subscription) {
            Log::warning('Stripe webhook: no subscription found for expired session', ['session_id' => $sessionId]);

            return;
        }

        if ($subscription->status !== SubscriptionStatus::PENDING) {
            Log::info('Stripe webhook: subscription no longer pending, skipping expiry', ['subscription_id' => $subscription->id]);

            return;
        }

        $previousStatus = $subscription->status->value;

        $subscription->update([
            'status' => SubscriptionStatus::EXPIRED->value,
        ]);

        $this->logStatusChange($subscription, $previousStatus, SubscriptionStatus::EXPIRED->value, 'Checkout session expired');

        $user = User::find($subscription->user_id);
        if ($user) {
            SubscriptionStatusChangedEvent::dispatch($user, [
                'subscription_id' => $subscription->id,
                'plan_name' => $subscription->plan?->name,
                'previous_status' => $previousStatus,
                'new_status' => SubscriptionStatus::EXPIRED->value,
                'reason' => 'Checkout session expired',
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        Log::info('Stripe webhook: subscription marked as expired', ['subscription_id' => $subscription->id]);
    }

    private function handleChargeRefunded(array $payload): void
    {
        $refundReason = data_get($payload, 'data.object.reason', 'requested_by');
        $refundedAmount = data_get($payload, 'data.object.amount_refunded', 0);
        $chargeAmount = data_get($payload, 'data.object.amount', 1);
        $isFullRefund = $refundedAmount >= $chargeAmount;

        if (! $isFullRefund) {
            Log::info('Stripe webhook: partial refund, skipping cancellation', [
                'refunded' => $refundedAmount,
                'total' => $chargeAmount,
            ]);

            return;
        }

        $checkoutSessionId = data_get($payload, 'data.object.metadata.checkout_session_id')
            ?? data_get($payload, 'data.object.metadata.stripe_checkout_session_id');

        $subscription = null;

        if ($checkoutSessionId) {
            $subscription = Subscription::where('stripe_checkout_session_id', $checkoutSessionId)->first();
        }

        if (! $subscription) {
            $paymentIntentId = data_get($payload, 'data.object.payment_intent');

            if ($paymentIntentId) {
                Log::info('Stripe webhook: cannot locate subscription for refund', [
                    'payment_intent' => $paymentIntentId,
                ]);
            }

            return;
        }

        if ($subscription->status === SubscriptionStatus::CANCELLED) {
            Log::info('Stripe webhook: subscription already cancelled', ['subscription_id' => $subscription->id]);

            return;
        }

        $previousStatus = $subscription->status->value;

        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED->value,
            'cancelled_at' => now(),
        ]);

        $this->logStatusChange($subscription, $previousStatus, SubscriptionStatus::CANCELLED->value, 'Full refund processed');

        $user = User::find($subscription->user_id);
        if ($user) {
            SubscriptionStatusChangedEvent::dispatch($user, [
                'subscription_id' => $subscription->id,
                'plan_name' => $subscription->plan?->name,
                'previous_status' => $previousStatus,
                'new_status' => SubscriptionStatus::CANCELLED->value,
                'reason' => 'Full refund processed',
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        Log::info('Stripe webhook: subscription cancelled due to refund', ['subscription_id' => $subscription->id]);
    }

    private function logStatusChange(Subscription $subscription, string $fromStatus, string $toStatus, string $reason): void
    {
        SubscriptionService::logStatusChange($subscription, $fromStatus, $toStatus, $reason);
    }
}
