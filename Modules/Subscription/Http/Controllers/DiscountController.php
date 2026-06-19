<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Subscription\DTOs\SubscriptionDiscountDTO;
use Modules\Subscription\Http\Requests\StoreDiscountRequest;
use Modules\Subscription\Http\Requests\UpdateDiscountRequest;
use Modules\Subscription\Http\Resources\SubscriptionDiscountResource;
use Modules\Subscription\Services\DiscountService;

class DiscountController extends Controller
{
    public function __construct(
        protected readonly DiscountService $discountService
    ) {
        $this->applyPermissions(
            'subscription_discounts',
            ['index', 'show', 'store', 'update', 'destroy']
        );
    }

    public function index()
    {
        $discounts = $this->discountService->all();

        return $this->paginatedResponse(SubscriptionDiscountResource::collection($discounts));
    }

    public function show($id)
    {
        $discount = $this->discountService->find((int) $id);

        return $this->successResponse(SubscriptionDiscountResource::make($discount));
    }

    public function store(StoreDiscountRequest $request)
    {
        $dto = SubscriptionDiscountDTO::fromRequest($request->validated());
        $discount = $this->discountService->store($dto);

        return $this->successResponse(SubscriptionDiscountResource::make($discount))->created('subscription_discount');
    }

    public function update(UpdateDiscountRequest $request, $id)
    {
        $dto = SubscriptionDiscountDTO::fromRequest($request->validated());
        $discount = $this->discountService->update((int) $id, $dto);

        return $this->successResponse(SubscriptionDiscountResource::make($discount))->updated('subscription_discount');
    }

    public function destroy($id)
    {
        $this->discountService->destroy((int) $id);

        return $this->successResponse()->deleted('subscription_discount');
    }
}
