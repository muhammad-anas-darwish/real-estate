<?php

namespace Modules\Ledger\DTOs;

use Illuminate\Support\Carbon;

final readonly class AccountEntryDTO
{
    public function __construct(
        public int $accountId,
        public ?string $batchId,
        public string $entryType,
        public float $amount,
        public string $currency,
        public float $balanceAfter,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public ?string $description = null,
        public ?string $idempotencyKey = null,
        public ?array $metadata = null,
        public ?Carbon $postedAt = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'account_id' => $this->accountId,
            'batch_id' => $this->batchId,
            'entry_type' => $this->entryType,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'balance_after' => $this->balanceAfter,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'description' => $this->description,
            'idempotency_key' => $this->idempotencyKey,
            'metadata' => $this->metadata,
            'posted_at' => $this->postedAt ?? now(),
        ], fn ($value) => $value !== null);
    }
}
