<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Subscription\Http\Requests\ValidateCouponRequest;
use Modules\Subscription\Http\Resources\SubscriptionDiscountResource;
use Modules\Subscription\Services\DiscountService;

class CouponController extends Controller
{
    public function __construct(
        protected readonly DiscountService $discountService
    ) {}

    public function validateCoupon(ValidateCouponRequest $request)
    {
        $validated = $request->validated();
        $result = $this->discountService->validateCoupon(
            $validated['code'],
            $validated['plan_id'] ?? null
        );

        if (! $result['valid']) {
            return $this->failedResponse($result['error'], 400);
        }

        $response = [
            'valid' => true,
            'discount' => SubscriptionDiscountResource::make($result['discount']),
        ];

        if ($result['pricing'] !== null) {
            $response['pricing'] = $result['pricing'];
        }

        return $this->successResponse($response);
    }
}
