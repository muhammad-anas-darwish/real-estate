<?php

namespace Modules\Ledger\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Entities\User;
use Modules\Ledger\Http\Resources\AccountEntryResource;
use Modules\Ledger\Http\Resources\AccountResource;
use Modules\Ledger\Services\AccountService;

class LedgerController extends Controller
{
    public function __construct(
        protected readonly AccountService $accountService
    ) {}

    public function balance()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->failedResponse('User not authenticated', 401);
        }

        $account = $this->accountService->getUserAccountOrNull($user);

        if (! $account) {
            return $this->successResponse([
                'current_balance' => 0,
                'held_balance' => 0,
                'available_balance' => 0,
                'currency' => 'USD',
            ]);
        }

        return $this->successResponse([
            'account' => AccountResource::make($account),
            'current_balance' => (float) $account->current_balance,
            'held_balance' => (float) $account->held_balance,
            'available_balance' => (float) $account->available_balance,
            'currency' => $account->currency,
        ]);
    }

    public function statement()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->failedResponse('User not authenticated', 401);
        }

        $entries = $this->accountService->getStatement($user);

        return $this->paginatedResponse(
            AccountEntryResource::collection($entries),
            'Ledger statement'
        );
    }
}
