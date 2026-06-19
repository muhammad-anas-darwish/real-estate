<?php

namespace Modules\Ledger\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends BaseModel
{
    use HasFactory;

    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'description',
        'debit_amount',
        'credit_amount',
        'sort_order',
        'ledger_batch_id',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
