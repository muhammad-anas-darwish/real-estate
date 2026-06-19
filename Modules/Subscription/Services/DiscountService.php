<?php

namespace Modules\Subscription\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Subscription\DTOs\SubscriptionDiscountDTO;
use Modules\Subscription\Entities\SubscriptionDiscount;
use Modules\Subscription\Entities\SubscriptionPlan;
use Modules\Subscription\Enums\DiscountType;

class DiscountService extends BaseService
{
    const CACHE_TAG = 'subscription-discounts';

    public function all(): LengthAwarePaginator
    {
        return SubscriptionDiscount::query()
            ->filter()
            ->with(['plan'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());
    }

    public function find(int $id): SubscriptionDiscount
    {
        return SubscriptionDiscount::with(['plan'])->findOrFail($id);
    }

    public function store(SubscriptionDiscountDTO $dto): SubscriptionDiscount
    {
        $discount = SubscriptionDiscount::create($dto->toArray());
        $this->clearCache();

        return $discount->fresh(['plan']);
    }

    public function update(int $id, SubscriptionDiscountDTO $dto): SubscriptionDiscount
    {
        $discount = SubscriptionDiscount::findOrFail($id);
        $discount->update($dto->toArray());
        $this->clearCache();

        return $discount->fresh(['plan']);
    }

    public function destroy(int $id): void
    {
        $discount = SubscriptionDiscount::findOrFail($id);
        $discount->delete();
        $this->clearCache();
    }

    public function validateCoupon(string $code, ?int $planId = null): array
    {
        $discount = SubscriptionDiscount::where('code', $code)->first();

        if (! $discount) {
            return [
                'valid' => false,
                'error' => 'Coupon code not found.',
            ];
        }

        if ($discount->isExpired()) {
            return [
                'valid' => false,
                'error' => 'This coupon code has expired.',
            ];
        }

        if ($discount->hasReachedMaxUses()) {
            return [
                'valid' => false,
                'error' => 'This coupon code has reached its maximum usage limit.',
            ];
        }

        if (! $discount->is_active) {
            return [
                'valid' => false,
                'error' => 'This coupon code is no longer active.',
            ];
        }

        if ($planId !== null && $discount->plan_id !== null && $discount->plan_id !== $planId) {
            return [
                'valid' => false,
                'error' => 'This coupon code does not apply to the selected plan.',
            ];
        }

        if ($planId !== null) {
            $plan = SubscriptionPlan::findOrFail($planId);
            $pricing = $this->calculateDiscountedPrice($discount, $plan->price);
        } else {
            $pricing = null;
        }

        return [
            'valid' => true,
            'discount' => $discount->load('plan'),
            'pricing' => $pricing,
        ];
    }

    public function calculateDiscountedPrice(SubscriptionDiscount $discount, string $originalPrice): array
    {
        $original = (float) $originalPrice;
        $discountAmount = 0.0;
        $finalPrice = $original;

        if ($discount->type === DiscountType::PERCENTAGE) {
            $discountAmount = $original * ((float) $discount->value / 100);
            $finalPrice = $original - $discountAmount;

            if ($finalPrice < 0) {
                $finalPrice = 0;
            }
        } elseif ($discount->type === DiscountType::FIXED) {
            $discountAmount = (float) $discount->value;
            $finalPrice = $original - $discountAmount;

            if ($finalPrice < 0) {
                $finalPrice = 0;
            }
        }

        return [
            'original_price' => round($original, 2),
            'discount_amount' => round($discountAmount, 2),
            'final_price' => round($finalPrice, 2),
            'discount_type' => $discount->type->value,
            'discount_value' => (float) $discount->value,
        ];
    }

    public function incrementUsage(SubscriptionDiscount $discount): void
    {
        $discount->increment('used_count');
    }
}
