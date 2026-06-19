<?php

namespace Modules\Ledger\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ledger\DTOs\AccountDTO;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Http\Requests\StoreAccountRequest;
use Modules\Ledger\Http\Requests\UpdateAccountRequest;
use Modules\Ledger\Http\Resources\AccountResource;
use Modules\Ledger\Services\AccountService;

class AccountController extends Controller
{
    public function __construct(
        protected readonly AccountService $accountService
    ) {
        $this->applyPermissions('accounts', ['index', 'show', 'store', 'update', 'destroy']);
    }

    public function tree(): JsonResponse
    {
        $category = request()->query('category');
        $accounts = $this->accountService->getTree($category);

        return $this->successResponse($accounts, 'Chart of accounts tree');
    }

    public function index(): JsonResponse
    {
        $accounts = $this->accountService->list();

        return $this->paginatedResponse(
            AccountResource::collection($accounts),
            'Accounts list'
        );
    }

    public function show(Account $account): JsonResponse
    {
        $account->load('parent', 'children');

        return $this->successResponse(
            AccountResource::make($account),
            'Account details'
        );
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $dto = AccountDTO::fromRequest($request->validated());
        $account = $this->accountService->create($dto);

        return $this->successResponse(
            AccountResource::make($account),
            'Account created'
        )->created('account');
    }

    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $dto = AccountDTO::fromRequest($request->validated());
        $account = $this->accountService->update($account, $dto);

        return $this->successResponse(
            AccountResource::make($account),
            'Account updated'
        )->updated('account');
    }

    public function destroy(Account $account): JsonResponse
    {
        $this->accountService->delete($account);

        return $this->successResponse([], 'Account deleted')
            ->deleted('account');
    }
}
