<?php

namespace Modules\ServiceProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\ServiceProvider\Services\ProviderBillingService;

class ProviderBillingController extends Controller
{
    public function __construct(
        protected readonly ProviderBillingService $billingService
    ) {}

    public function balance()
    {
        $balance = $this->billingService->getProviderBalance(Auth::id());

        return $this->successResponse($balance);
    }

    public function earnings()
    {
        $earnings = $this->billingService->getProviderEarnings(Auth::id());

        return $this->paginatedResponse($earnings);
    }

    public function withdraw()
    {
        $amount = (float) request('amount', 0);

        $result = $this->billingService->withdraw(Auth::id(), $amount);

        return $this->successResponse($result);
    }
}
