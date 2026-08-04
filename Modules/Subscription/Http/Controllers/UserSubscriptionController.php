<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Subscription\Http\Resources\ActiveSubscriptionResource;
use Modules\Subscription\Http\Resources\SubscriptionResource;
use Modules\Subscription\Http\Resources\SubscriptionStatusLogResource;
use Modules\Subscription\Services\SubscriptionAccess;
use Modules\Subscription\Services\SubscriptionService;

class UserSubscriptionController extends Controller
{
    public function __construct(
        protected readonly SubscriptionService $subscriptionService,
        protected readonly SubscriptionAccess $subscriptionAccess
    ) {}

    public function current()
    {
        $user = auth()->user();
        $data = $this->subscriptionAccess->getActiveSubscription($user);

        if (! $data) {
            return $this->notFoundResponse('No active subscription found.');
        }

        return $this->successResponse(ActiveSubscriptionResource::make($data));
    }

    public function history()
    {
        $user = auth()->user();
        $subscriptions = $this->subscriptionService->getSubscriptionHistory($user);

        return $this->paginatedResponse(SubscriptionResource::collection($subscriptions));
    }

    public function statusLogs($id)
    {
        $user = auth()->user();
        $subscription = $user->subscriptions()->findOrFail($id);
        $logs = $subscription->statusLogs()->orderBy('created_at', 'desc')->paginate(15);

        return $this->paginatedResponse(SubscriptionStatusLogResource::collection($logs));
    }

    public function cancel()
    {
        $user = auth()->user();

        try {
            $subscription = $this->subscriptionService->cancel($user);

            return $this->successResponse(
                SubscriptionResource::make($subscription->load(['plan', 'discount']))
            )->updated('subscription');
        } catch (\RuntimeException $e) {
            return $this->failedResponse($e->getMessage(), 400);
        }
    }

    public function featureAccess()
    {
        $user = auth()->user();
        $access = $this->subscriptionAccess->getAllFeatureAccess($user);

        return $this->successResponse($access);
    }

    public function checkFeature(string $slug)
    {
        $user = auth()->user();
        $access = $this->subscriptionAccess->getFeatureAccess($user, $slug);

        return $this->successResponse($access);
    }
}
