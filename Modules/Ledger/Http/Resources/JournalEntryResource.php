<?php

namespace Modules\Ledger\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class JournalEntryResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'journal_number' => $this->journal_number,
            'entry_date' => $this->entry_date?->format('Y-m-d'),
            'description' => $this->description,
            'reference' => $this->reference,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'notes' => $this->notes,
            'total_debit' => (float) $this->total_debit,
            'total_credit' => (float) $this->total_credit,
            'is_balanced' => $this->isBalanced(),
            'posted_at' => $this->posted_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'lines' => JournalEntryLineResource::class,
            'createdBy' => \Modules\Auth\Http\Resources\UserResource::class,
            'postedBy' => \Modules\Auth\Http\Resources\UserResource::class,
        ];
    }
}
