<?php

namespace Modules\Ledger\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountEntry extends BaseModel
{
    use HasFactory;

    protected $table = 'ledger_account_entries';

    protected $fillable = [
        'account_id',
        'batch_id',
        'entry_type',
        'amount',
        'currency',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'idempotency_key',
        'metadata',
        'posted_at',
    ];

    protected $casts = [
        'entry_type' => \Modules\Ledger\Enums\EntryType::class,
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'posted_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'account_id',
        'entry_type',
        'currency',
        'reference_type',
    ];

    protected static $dateFilterableColumns = [
        'posted_at',
        'created_at',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function reference()
    {
        // Dynamic morph - resolve via reference_type string
        if ($this->reference_type && $this->reference_id) {
            $class = $this->resolveReferenceClass($this->reference_type);
            if ($class) {
                return $class::find($this->reference_id);
            }
        }

        return null;
    }

    private function resolveReferenceClass(string $type): ?string
    {
        return match ($type) {
            'ad_payment', 'subscription_payment' => \Modules\RealEstate\Entities\Ad::class,
            'wallet_topup' => null,
            'journal_entry' => \Modules\Ledger\Entities\JournalEntry::class,
            'service_payment', 'service_earning', 'service_commission' => \Modules\ServiceProvider\Entities\ServiceRequest::class,
            default => null,
        };
    }
}
