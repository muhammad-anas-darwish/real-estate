<?php

namespace Modules\Subscription\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Subscription\Services\SubscriptionAccess;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(
        private readonly SubscriptionAccess $subscriptionAccess
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $this->subscriptionAccess->hasActiveSubscription($user)) {
            return response()->json([
                'success' => false,
                'message' => 'An active subscription is required to access this resource.',
            ], 403);
        }

        $subscription = $user->activeSubscription();

        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to continue.',
            ], 403);
        }

        return $next($request);
    }
}
