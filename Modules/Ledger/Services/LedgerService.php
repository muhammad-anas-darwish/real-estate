<?php

namespace Modules\Ledger\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Entities\AccountEntry;
use Modules\Ledger\Enums\EntryType;

class LedgerService extends BaseService
{
    public const CACHE_TAG = 'ledger';

    /**
     * Transfer funds between two accounts using double-entry bookkeeping.
     * Creates a debit entry on the source account and a credit entry on the destination account.
     *
     * @throws \RuntimeException if insufficient balance
     */
    public function transfer(
        Account $from,
        Account $to,
        float $amount,
        string $currency,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
    ): array {
        if ($amount <= 0) {
            throw new \RuntimeException('Transfer amount must be positive.');
        }

        if ($idempotencyKey && $this->isDuplicate($idempotencyKey)) {
            return $this->getBatchEntries($idempotencyKey);
        }

        return DB::transaction(function () use (
            $from, $to, $amount, $currency, $referenceType, $referenceId, $description, $idempotencyKey
        ) {
            $batchId = $idempotencyKey ?? (string) \Illuminate\Support\Str::uuid();

            $from = Account::where('id', $from->id)->lockForUpdate()->firstOrFail();
            $to = Account::where('id', $to->id)->lockForUpdate()->firstOrFail();

            $available = (float) $from->current_balance - (float) $from->held_balance;

            if ($available < $amount) {
                throw new \RuntimeException("Insufficient available balance. Available: {$available}, Required: {$amount}");
            }

            $fromBalanceAfter = (float) $from->current_balance - $amount;
            $toBalanceAfter = (float) $to->current_balance + $amount;

            $debitEntry = AccountEntry::create($this->buildEntryData(
                accountId: $from->id,
                batchId: $batchId,
                entryType: EntryType::DEBIT,
                amount: $amount,
                currency: $currency,
                balanceAfter: $fromBalanceAfter,
                referenceType: $referenceType,
                referenceId: $referenceId,
                description: $description,
                idempotencyKey: $idempotencyKey,
            ));

            $creditEntry = AccountEntry::create($this->buildEntryData(
                accountId: $to->id,
                batchId: $batchId,
                entryType: EntryType::CREDIT,
                amount: $amount,
                currency: $currency,
                balanceAfter: $toBalanceAfter,
                referenceType: $referenceType,
                referenceId: $referenceId,
                description: $description,
                idempotencyKey: $idempotencyKey,
            ));

            $from->update(['current_balance' => $fromBalanceAfter]);
            $to->update(['current_balance' => $toBalanceAfter]);

            return [
                'batch_id' => $batchId,
                'debit' => $debitEntry,
                'credit' => $creditEntry,
            ];
        });
    }

    /**
     * Credit a single account (deposit / add funds).
     */
    public function credit(
        Account $account,
        float $amount,
        string $currency,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
    ): AccountEntry {
        if ($amount <= 0) {
            throw new \RuntimeException('Credit amount must be positive.');
        }

        if ($idempotencyKey && $this->isDuplicate($idempotencyKey)) {
            return AccountEntry::where('idempotency_key', $idempotencyKey)->firstOrFail();
        }

        return DB::transaction(function () use (
            $account, $amount, $currency, $referenceType, $referenceId, $description, $idempotencyKey
        ) {
            $account = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();
            $balanceAfter = (float) $account->current_balance + $amount;

            $entry = AccountEntry::create($this->buildEntryData(
                accountId: $account->id,
                batchId: $idempotencyKey ?? (string) \Illuminate\Support\Str::uuid(),
                entryType: EntryType::CREDIT,
                amount: $amount,
                currency: $currency,
                balanceAfter: $balanceAfter,
                referenceType: $referenceType,
                referenceId: $referenceId,
                description: $description,
                idempotencyKey: $idempotencyKey,
            ));

            $account->update(['current_balance' => $balanceAfter]);

            return $entry;
        });
    }

    /**
     * Debit a single account (withdraw / charge).
     */
    public function debit(
        Account $account,
        float $amount,
        string $currency,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
    ): AccountEntry {
        if ($amount <= 0) {
            throw new \RuntimeException('Debit amount must be positive.');
        }

        if ($idempotencyKey && $this->isDuplicate($idempotencyKey)) {
            return AccountEntry::where('idempotency_key', $idempotencyKey)->firstOrFail();
        }

        return DB::transaction(function () use (
            $account, $amount, $currency, $referenceType, $referenceId, $description, $idempotencyKey
        ) {
            $account = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();
            $available = (float) $account->current_balance - (float) $account->held_balance;

            if ($available < $amount) {
                throw new \RuntimeException("Insufficient available balance. Available: {$available}, Required: {$amount}");
            }

            $balanceAfter = (float) $account->current_balance - $amount;

            $entry = AccountEntry::create($this->buildEntryData(
                accountId: $account->id,
                batchId: $idempotencyKey ?? (string) \Illuminate\Support\Str::uuid(),
                entryType: EntryType::DEBIT,
                amount: $amount,
                currency: $currency,
                balanceAfter: $balanceAfter,
                referenceType: $referenceType,
                referenceId: $referenceId,
                description: $description,
                idempotencyKey: $idempotencyKey,
            ));

            $account->update(['current_balance' => $balanceAfter]);

            return $entry;
        });
    }

    /**
     * Hold a portion of balance (for pending operations).
     */
    public function hold(Account $account, float $amount): void
    {
        DB::transaction(function () use ($account, $amount) {
            $account = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();
            $available = (float) $account->current_balance - (float) $account->held_balance;

            if ($available < $amount) {
                throw new \RuntimeException('Insufficient available balance for hold.');
            }

            $account->update(['held_balance' => (float) $account->held_balance + $amount]);
        });
    }

    /**
     * Release a previously held balance.
     */
    public function releaseHold(Account $account, float $amount): void
    {
        DB::transaction(function () use ($account, $amount) {
            $account = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();

            if ((float) $account->held_balance < $amount) {
                throw new \RuntimeException('Cannot release more than held balance.');
            }

            $account->update(['held_balance' => (float) $account->held_balance - $amount]);
        });
    }

    private function buildEntryData(
        int $accountId,
        string $batchId,
        EntryType $entryType,
        float $amount,
        string $currency,
        float $balanceAfter,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
    ): array {
        return array_filter([
            'account_id' => $accountId,
            'batch_id' => $batchId,
            'entry_type' => $entryType,
            'amount' => $amount,
            'currency' => $currency,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'idempotency_key' => $idempotencyKey,
            'posted_at' => now(),
        ], fn ($value) => $value !== null);
    }

    private function isDuplicate(string $idempotencyKey): bool
    {
        return AccountEntry::where('idempotency_key', $idempotencyKey)->exists();
    }

    private function getBatchEntries(string $idempotencyKey): array
    {
        $entries = AccountEntry::where('idempotency_key', $idempotencyKey)->get();

        return [
            'batch_id' => $idempotencyKey,
            'debit' => $entries->where('entry_type', EntryType::DEBIT)->first(),
            'credit' => $entries->where('entry_type', EntryType::CREDIT)->first(),
        ];
    }
}
