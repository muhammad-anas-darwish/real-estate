<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Subscription\Http\Requests\CheckoutRequest;
use Modules\Subscription\Http\Resources\CheckoutResource;
use Modules\Subscription\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function __construct(
        protected readonly CheckoutService $checkoutService
    ) {}

    public function checkout(CheckoutRequest $request)
    {
        $validated = $request->validated();

        $result = $this->checkoutService->createCheckoutSession(
            userId: $request->user()->id,
            planId: $validated['plan_id'],
            couponCode: $validated['coupon_code'] ?? null
        );

        if (! $result['success']) {
            return $this->failedResponse($result['error'], 400);
        }

        return $this->successResponse(CheckoutResource::make($result));
    }
}
