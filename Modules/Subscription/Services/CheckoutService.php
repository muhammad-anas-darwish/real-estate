<?php

namespace Modules\Subscription\Services;

use App\Services\BaseService;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionPlan;
use Modules\Subscription\Enums\SubscriptionStatus;
use Stripe\StripeClient;

class CheckoutService extends BaseService
{
    const CACHE_TAG = 'subscription-checkouts';

    public function __construct(
        private readonly DiscountService $discountService
    ) {}

    public function createCheckoutSession(int $userId, int $planId, ?string $couponCode = null): array
    {
        $plan = SubscriptionPlan::active()->findOrFail($planId);

        $discount = null;
        $pricing = null;

        if ($couponCode) {
            $result = $this->discountService->validateCoupon($couponCode, $planId);

            if (! $result['valid']) {
                return [
                    'success' => false,
                    'error' => $result['error'],
                ];
            }

            $discount = $result['discount'];
            $pricing = $result['pricing'];
        }

        $existing = $this->findPendingSubscription($userId, $planId);
        if ($existing && $existing->stripe_checkout_session_id) {
            $stripe = $this->getStripeClient();

            try {
                $session = $stripe->checkout->sessions->retrieve($existing->stripe_checkout_session_id);

                if ($session->status === 'open') {
                    return [
                        'success' => true,
                        'checkout_session_id' => $session->id,
                        'checkout_url' => $session->url,
                        'subscription_id' => $existing->id,
                        'plan' => $plan,
                        'pricing' => $pricing ?? $this->buildPricingWithoutDiscount($plan),
                        'discount' => $discount,
                    ];
                }
            } catch (\Exception) {
                // Session expired or invalid, create a new one
            }
        }

        $finalPrice = $pricing ? $pricing['final_price'] : (float) $plan->price;
        $currency = strtolower($plan->currency ?? 'usd');

        $stripe = $this->getStripeClient();

        $sessionData = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $plan->name,
                        'description' => $plan->description ?? "Subscription to {$plan->name}",
                    ],
                    'unit_amount' => (int) round($finalPrice * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => config('subscription.checkout_success_url').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('subscription.checkout_cancel_url'),
            'metadata' => [
                'user_id' => (string) $userId,
                'plan_id' => (string) $planId,
                'discount_id' => $discount ? (string) $discount->id : '',
                'coupon_code' => $couponCode ?? '',
                'final_price' => (string) $finalPrice,
            ],
        ];

        $session = $stripe->checkout->sessions->create($sessionData);

        $subscriptionData = [
            'user_id' => $userId,
            'plan_id' => $planId,
            'discount_id' => $discount?->id,
            'stripe_checkout_session_id' => $session->id,
            'currency' => $plan->currency ?? 'USD',
            'status' => SubscriptionStatus::PENDING->value,
        ];

        if ($existing) {
            $existing->update($subscriptionData);
            $subscription = $existing->fresh();
        } else {
            $subscription = Subscription::create($subscriptionData);
        }

        return [
            'success' => true,
            'checkout_session_id' => $session->id,
            'checkout_url' => $session->url,
            'subscription_id' => $subscription->id,
            'plan' => $plan,
            'pricing' => $pricing ?? $this->buildPricingWithoutDiscount($plan),
            'discount' => $discount,
        ];
    }

    private function findPendingSubscription(int $userId, int $planId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('plan_id', $planId)
            ->where('status', SubscriptionStatus::PENDING->value)
            ->first();
    }

    private function buildPricingWithoutDiscount(SubscriptionPlan $plan): array
    {
        return [
            'original_price' => (float) $plan->price,
            'discount_amount' => 0.0,
            'final_price' => (float) $plan->price,
            'discount_type' => null,
            'discount_value' => null,
        ];
    }

    private function getStripeClient(): StripeClient
    {
        return new StripeClient(config('subscription.secret_key'));
    }
}
