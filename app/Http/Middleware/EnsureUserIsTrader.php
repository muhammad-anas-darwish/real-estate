<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTrader
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || (! $user->hasRole('trader') && ! $user->hasRole('super-admin'))) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized_trader_access'),
            ], 403);
        }

        return $next($request);
    }
}
