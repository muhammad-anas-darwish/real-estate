<?php

namespace Modules\Ledger\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class AccountEntryResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'batch_id' => $this->batch_id,
            'entry_type' => $this->entry_type?->value,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'balance_after' => (float) $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'description' => $this->description,
            'idempotency_key' => $this->idempotency_key,
            'metadata' => $this->metadata,
            'posted_at' => $this->posted_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'account' => AccountResource::class,
        ];
    }
}
