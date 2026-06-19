<?php

namespace Modules\Ledger\Services;

use App\Services\BaseService;
use Modules\Auth\Entities\User;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Entities\AccountEntry;
use Modules\Ledger\Enums\AccountType;

class AccountService extends BaseService
{
    public const CACHE_TAG = 'ledger_accounts';

    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * Get or create the user's balance account (wallet replacement).
     */
    public function getUserAccount(User $user, string $currency = 'USD'): Account
    {
        $account = Account::where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->where('type', AccountType::USER_BALANCE)
            ->first();

        if (! $account) {
            $account = Account::create([
                'code' => $this->generateAccountCode($user),
                'name' => "Balance: {$user->name}",
                'type' => AccountType::USER_BALANCE,
                'currency' => $currency,
                'owner_type' => User::class,
                'owner_id' => $user->id,
                'current_balance' => 0,
                'held_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $account;
    }

    /**
     * Get or create a system account (revenue, clearing, etc.).
     */
    public function getSystemAccount(AccountType $type, string $currency = 'USD'): Account
    {
        $code = config("ledger.{$type->value}_account", strtoupper(substr($type->value, 0, 3)).'-001');

        $account = Account::where('code', $code)->first();

        if (! $account) {
            $account = Account::create([
                'code' => $code,
                'name' => $type->label(),
                'type' => $type,
                'currency' => $currency,
                'current_balance' => 0,
                'held_balance' => 0,
                'is_active' => true,
            ]);
        }

        return $account;
    }

    public function getBalance(User $user): float
    {
        $account = Account::where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->where('type', AccountType::USER_BALANCE)
            ->first();

        return (float) ($account?->current_balance ?? 0);
    }

    public function getAvailableBalance(User $user): float
    {
        $account = Account::where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->where('type', AccountType::USER_BALANCE)
            ->first();

        if (! $account) {
            return 0;
        }

        return (float) $account->available_balance;
    }

    public function getUserAccountOrNull(User $user): ?Account
    {
        return Account::where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->where('type', AccountType::USER_BALANCE)
            ->first();
    }

    public function getStatement(User $user, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        $account = $this->getUserAccountOrNull($user);

        if (! $account) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return AccountEntry::where('account_id', $account->id)
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function generateAccountCode(User $user): string
    {
        $count = Account::where('type', AccountType::USER_BALANCE)->lockForUpdate()->count();

        return sprintf('USR-%03d-BAL', $count + 1);
    }
}
