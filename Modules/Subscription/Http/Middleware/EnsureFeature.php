<?php

namespace Modules\Subscription\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Subscription\Services\SubscriptionAccess;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeature
{
    public function __construct(
        private readonly SubscriptionAccess $subscriptionAccess
    ) {}

    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        foreach ($features as $featureSlug) {
            $access = $this->subscriptionAccess->getFeatureAccess($user, $featureSlug);

            if (! $access['has_access']) {
                return response()->json([
                    'success' => false,
                    'message' => $access['reason'] ?? 'You do not have access to the required feature.',
                    'feature' => $featureSlug,
                ], 403);
            }
        }

        return $next($request);
    }
}
