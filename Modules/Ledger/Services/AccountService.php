<?php

namespace Modules\Ledger\Services;

use App\Services\BaseService;
use Modules\Auth\Entities\User;
use Modules\Ledger\DTOs\AccountDTO;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Entities\AccountEntry;
use Modules\Ledger\Enums\AccountType;
use Modules\Ledger\Http\Resources\AccountResource;

class AccountService extends BaseService
{
    public const CACHE_TAG = 'ledger_accounts';

    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    public function getTree(?string $category = null): array
    {
        $query = Account::whereNull('parent_id')
            ->where('is_active', true);

        if ($category) {
            $query->where('account_category', $category);
        }

        $roots = $query->with(['children' => function ($q) {
            $q->orderBy('sort_order')->orderBy('account_number');
        }, 'children.children' => function ($q) {
            $q->orderBy('sort_order')->orderBy('account_number');
        }])
        ->orderBy('sort_order')
        ->orderBy('account_number')
        ->get();

        return AccountResource::collection(
            $roots->map(function ($account) {
                $account->setRelation('children', $account->children);

                return $account;
            })
        )->toArray(request());
    }

    public function list(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Account::query()
            ->with('parent')
            ->orderBy('account_number')
            ->orderBy('sort_order')
            ->filter()
            ->paginate($this->getPerPage());
    }

    public function find(int $id): Account
    {
        return Account::with('parent', 'children')->findOrFail($id);
    }

    public function create(AccountDTO $dto): Account
    {
        $data = array_merge($dto->toArray(), [
            'type' => AccountType::LIABILITY,
            'currency' => $dto->currency ?? 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => $dto->isActive ?? true,
        ]);

        $account = Account::create($data);
        $this->clearCache();

        return $account;
    }

    public function update(Account $account, AccountDTO $dto): Account
    {
        $account->update($dto->toArray());
        $this->clearCache();

        return $account;
    }

    public function delete(Account $account): void
    {
        if ($account->entries()->exists()) {
            throw new \RuntimeException('Cannot delete account with existing entries. Deactivate it instead.');
        }

        if ($account->children()->exists()) {
            throw new \RuntimeException('Cannot delete account with child accounts. Remove child accounts first.');
        }

        $account->delete();
        $this->clearCache();
    }

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
