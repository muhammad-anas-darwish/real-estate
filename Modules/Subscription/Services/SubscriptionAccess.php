<?php

namespace Modules\Subscription\Services;

use Carbon\Carbon;
use Modules\Auth\Entities\User;
use Modules\Subscription\Entities\SubscriptionFeature;
use Modules\Subscription\Entities\SubscriptionPlan;
use Modules\Subscription\Entities\SubscriptionPlanFeature;
use Modules\Subscription\Enums\FeatureType;

class SubscriptionAccess
{
    public function hasActiveSubscription(User $user): bool
    {
        return $user->activeSubscription() !== null;
    }

    public function getActiveSubscription(User $user): ?array
    {
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return null;
        }

        $plan = $subscription->plan;
        $daysRemaining = null;

        if ($subscription->ends_at) {
            $daysRemaining = (int) Carbon::now()->diffInDays($subscription->ends_at, false);
            if ($daysRemaining < 0) {
                $daysRemaining = 0;
            }
        }

        $features = $this->getPlanFeatures($plan);

        return [
            'subscription' => $subscription,
            'plan' => $plan,
            'days_remaining' => $daysRemaining,
            'features' => $features,
        ];
    }

    public function hasFeature(User $user, string $featureSlug): bool
    {
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return false;
        }

        $planFeature = $this->findPlanFeature($subscription->plan_id, $featureSlug);

        if (! $planFeature) {
            return false;
        }

        if ($planFeature->feature->type === FeatureType::TOGGLE) {
            return $planFeature->is_enabled;
        }

        if ($planFeature->feature->type === FeatureType::LIMIT) {
            return $planFeature->limit_value !== null && $planFeature->limit_value > 0;
        }

        return false;
    }

    public function getFeatureLimit(User $user, string $featureSlug): ?int
    {
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return null;
        }

        $planFeature = $this->findPlanFeature($subscription->plan_id, $featureSlug);

        if (! $planFeature) {
            return null;
        }

        if ($planFeature->feature->type !== FeatureType::LIMIT) {
            return null;
        }

        return $planFeature->limit_value;
    }

    public function getFeatureAccess(User $user, string $featureSlug): array
    {
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return [
                'has_access' => false,
                'reason' => 'No active subscription',
                'feature' => null,
                'value' => null,
            ];
        }

        $planFeature = $this->findPlanFeature($subscription->plan_id, $featureSlug);

        if (! $planFeature) {
            return [
                'has_access' => false,
                'reason' => 'Feature not part of your plan',
                'feature' => null,
                'value' => null,
            ];
        }

        $feature = $planFeature->feature;

        if ($feature->type === FeatureType::TOGGLE) {
            return [
                'has_access' => $planFeature->is_enabled,
                'reason' => $planFeature->is_enabled ? null : 'Feature is disabled on your plan',
                'feature' => $feature->slug,
                'value' => $planFeature->is_enabled,
            ];
        }

        return [
            'has_access' => $planFeature->limit_value !== null && $planFeature->limit_value > 0,
            'reason' => ($planFeature->limit_value !== null && $planFeature->limit_value > 0) ? null : 'Feature limit is zero on your plan',
            'feature' => $feature->slug,
            'value' => $planFeature->limit_value,
        ];
    }

    public function getAllFeatureAccess(User $user): array
    {
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return [];
        }

        $plan = SubscriptionPlan::with('features')->find($subscription->plan_id);

        if (! $plan) {
            return [];
        }

        $access = [];
        foreach ($plan->features as $feature) {
            $pivot = $feature->pivot;
            $access[$feature->slug] = [
                'name' => $feature->name,
                'slug' => $feature->slug,
                'type' => $feature->type->value,
                'is_enabled' => $pivot->is_enabled,
                'limit_value' => $pivot->limit_value,
            ];
        }

        return $access;
    }

    private function getPlanFeatures(?SubscriptionPlan $plan): array
    {
        if (! $plan) {
            return [];
        }

        $plan->load('features');

        $features = [];
        foreach ($plan->features as $feature) {
            $pivot = $feature->pivot;
            $features[] = [
                'name' => $feature->name,
                'slug' => $feature->slug,
                'type' => $feature->type->value,
                'is_enabled' => $pivot->is_enabled,
                'limit_value' => $pivot->limit_value,
            ];
        }

        return $features;
    }

    private function findPlanFeature(int $planId, string $featureSlug): ?SubscriptionPlanFeature
    {
        $feature = SubscriptionFeature::where('slug', $featureSlug)->first();

        if (! $feature) {
            return null;
        }

        return SubscriptionPlanFeature::where('plan_id', $planId)
            ->where('feature_id', $feature->id)
            ->first();
    }
}
