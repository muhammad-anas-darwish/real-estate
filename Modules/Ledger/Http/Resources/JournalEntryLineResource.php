<?php

namespace Modules\Ledger\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class JournalEntryLineResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'journal_entry_id' => $this->journal_entry_id,
            'account_id' => $this->account_id,
            'description' => $this->description,
            'debit_amount' => (float) $this->debit_amount,
            'credit_amount' => (float) $this->credit_amount,
            'sort_order' => $this->sort_order,
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
