<?php

namespace Modules\Subscription\Services;

use App\Services\BaseService;
use Modules\Ledger\Enums\AccountType;
use Modules\Ledger\Services\AccountService;
use Modules\Ledger\Services\LedgerService;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionPlan;
use Modules\Subscription\Enums\SubscriptionStatus;
use Stripe\StripeClient;

class CheckoutService extends BaseService
{
    const CACHE_TAG = 'subscription-checkouts';

    public function __construct(
        private readonly DiscountService $discountService,
        private readonly LedgerService $ledgerService,
        private readonly AccountService $accountService,
    ) {}

    public function createCheckoutSession(int $userId, int $planId, ?string $couponCode = null, string $paymentMethod = 'stripe'): array
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

        $finalPrice = $pricing ? $pricing['final_price'] : (float) $plan->price;
        $currency = strtolower($plan->currency ?? 'usd');

        if ($paymentMethod === 'balance') {
            return $this->checkoutWithBalance($userId, $planId, $plan, $discount, $finalPrice, $currency);
        }

        return $this->checkoutWithStripe($userId, $planId, $plan, $discount, $pricing, $couponCode, $finalPrice, $currency);
    }

    private function checkoutWithBalance(int $userId, int $planId, SubscriptionPlan $plan, $discount, float $finalPrice, string $currency): array
    {
        $user = \Modules\Auth\Entities\User::findOrFail($userId);
        $userAccount = $this->accountService->getUserAccount($user, strtoupper($currency));
        $revenueAccount = $this->accountService->getSystemAccount(AccountType::REVENUE, strtoupper($currency));

        $subscription = Subscription::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'discount_id' => $discount?->id,
            'currency' => strtoupper($currency),
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_at' => now(),
            'ends_at' => now()->addDays($plan->duration_days),
        ]);

        try {
            $idempotencyKey = 'sub_'.$subscription->id.'_'.\Illuminate\Support\Str::uuid();
            $result = $this->ledgerService->transfer(
                from: $userAccount,
                to: $revenueAccount,
                amount: $finalPrice,
                currency: strtoupper($currency),
                referenceType: 'subscription_payment',
                referenceId: $subscription->id,
                description: "Subscription: {$plan->name}",
                idempotencyKey: $idempotencyKey,
            );

            $subscription->update(['payment_reference' => $result['batch_id']]);
        } catch (\RuntimeException $e) {
            $subscription->update(['status' => SubscriptionStatus::CANCELLED->value]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'subscription_id' => $subscription->id,
            'plan' => $plan,
            'pricing' => $this->buildPricingWithoutDiscount($plan),
            'discount' => $discount,
            'payment_method' => 'balance',
        ];
    }

    private function checkoutWithStripe(int $userId, int $planId, SubscriptionPlan $plan, $discount, $pricing, $couponCode, float $finalPrice, string $currency): array
    {
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
                        'payment_method' => 'stripe',
                    ];
                }
            } catch (\Exception) {
                // Session expired or invalid, create a new one
            }
        }

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
            'payment_method' => 'stripe',
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
