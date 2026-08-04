<?php

namespace Modules\ServiceProvider\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\User;
use Modules\Ledger\Entities\AccountEntry;
use Modules\Ledger\Services\AccountService;
use Modules\Ledger\Services\LedgerService;
use Modules\ServiceProvider\Entities\ServiceCommissionConfig;
use Modules\ServiceProvider\Entities\ServiceRequest;

class ProviderBillingService extends BaseService
{
    public const CACHE_TAG = 'provider_billing';

    public function __construct(
        protected readonly LedgerService $ledgerService,
        protected readonly AccountService $accountService,
    ) {}

    public function settlePayment(ServiceRequest $request): array
    {
        if ($request->is_paid) {
            throw new \RuntimeException('This service request has already been settled.');
        }

        if (! $request->price || $request->price <= 0) {
            return ['message' => 'No payment required for free service.'];
        }

        $commissionRate = ServiceCommissionConfig::getCommissionRate($request->service_type->value);
        $commissionAmount = round($request->price * ($commissionRate / 100), 2);
        $providerAmount = round($request->price - $commissionAmount, 2);

        return DB::transaction(function () use ($request, $commissionAmount, $providerAmount) {
            $client = User::findOrFail($request->client_id);
            $providerUser = $request->provider?->user;

            $clientAccount = $this->accountService->getUserAccount($client);
            $platformAccount = $this->accountService->getSystemAccount(
                \Modules\Ledger\Enums\AccountType::PLATFORM_FEE
            );

            if (! $providerUser) {
                throw new \RuntimeException('No provider assigned to this request.');
            }

            $providerAccount = $this->accountService->getUserAccount($providerUser);

            $batchId = 'sr-'.$request->id.'-'.now()->timestamp;

            // Debit client → hold temporarily, then transfer
            $this->ledgerService->debit(
                account: $clientAccount,
                amount: $request->price,
                currency: 'USD',
                referenceType: 'service_payment',
                referenceId: $request->id,
                description: "Payment for {$request->service_type->value} service (Request #{$request->id})",
                idempotencyKey: $batchId.'-client',
            );

            // Credit provider
            $this->ledgerService->credit(
                account: $providerAccount,
                amount: $providerAmount,
                currency: 'USD',
                referenceType: 'service_earning',
                referenceId: $request->id,
                description: "Earnings from {$request->service_type->value} service (Request #{$request->id})",
                idempotencyKey: $batchId.'-provider',
            );

            // Credit platform commission
            $this->ledgerService->credit(
                account: $platformAccount,
                amount: $commissionAmount,
                currency: 'USD',
                referenceType: 'service_commission',
                referenceId: $request->id,
                description: "Commission from {$request->service_type->value} service (Request #{$request->id})",
                idempotencyKey: $batchId.'-platform',
            );

            $request->update([
                'is_paid' => true,
                'paid_at' => now(),
                'platform_fee' => $commissionAmount,
                'provider_earnings' => $providerAmount,
                'is_provider_paid' => true,
            ]);

            return [
                'message' => 'Payment settled successfully.',
                'amount' => $request->price,
                'platform_fee' => $commissionAmount,
                'provider_earnings' => $providerAmount,
                'batch_id' => $batchId,
            ];
        });
    }

    public function getProviderEarnings(int $providerUserId, int $perPage = 20): LengthAwarePaginator
    {
        $user = User::findOrFail($providerUserId);
        $account = $this->accountService->getUserAccountOrNull($user);

        if (! $account) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        return AccountEntry::where('account_id', $account->id)
            ->where('entry_type', \Modules\Ledger\Enums\EntryType::CREDIT)
            ->where('reference_type', 'service_earning')
            ->orderByDesc('posted_at')
            ->paginate($perPage);
    }

    public function getProviderBalance(int $providerUserId): array
    {
        $user = User::findOrFail($providerUserId);
        $account = $this->accountService->getUserAccountOrNull($user);

        $totalEarnings = 0;
        if ($account) {
            $totalEarnings = (float) AccountEntry::where('account_id', $account->id)
                ->where('entry_type', \Modules\Ledger\Enums\EntryType::CREDIT)
                ->where('reference_type', 'service_earning')
                ->sum('amount');
        }

        return [
            'current_balance' => (float) ($account?->current_balance ?? 0),
            'available_balance' => (float) ($account?->available_balance ?? 0),
            'total_earnings' => $totalEarnings,
        ];
    }

    public function withdraw(int $userId, float $amount): array
    {
        if ($amount <= 0) {
            throw new \RuntimeException('Withdrawal amount must be positive.');
        }

        return DB::transaction(function () use ($userId, $amount) {
            $user = User::findOrFail($userId);
            $account = $this->accountService->getUserAccount($user);

            $available = (float) $account->available_balance;

            if ($available < $amount) {
                throw new \RuntimeException("Insufficient balance. Available: {$available}, Requested: {$amount}.");
            }

            $this->ledgerService->debit(
                account: $account,
                amount: $amount,
                currency: 'USD',
                referenceType: 'withdrawal',
                referenceId: null,
                description: "Withdrawal by {$user->name}",
            );

            return [
                'message' => 'Withdrawal processed successfully.',
                'amount' => $amount,
                'remaining_balance' => (float) $account->fresh()->available_balance,
            ];
        });
    }
}
