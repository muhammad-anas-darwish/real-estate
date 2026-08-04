<?php

namespace Modules\Ledger\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\Ledger\Enums\JournalEntryStatus;

class JournalEntry extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'journal_entries';

    protected $fillable = [
        'journal_number',
        'entry_date',
        'description',
        'reference',
        'status',
        'notes',
        'created_by_id',
        'posted_by_id',
        'posted_at',
    ];

    protected $casts = [
        'status' => JournalEntryStatus::class,
        'entry_date' => 'date',
        'posted_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'status',
        'created_by_id',
    ];

    protected static $searchableColumns = [
        'journal_number',
        'description',
        'reference',
    ];

    protected static $dateFilterableColumns = [
        'entry_date',
        'posted_at',
        'created_at',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function isPosted(): bool
    {
        return $this->status === JournalEntryStatus::POSTED;
    }

    public function isDraft(): bool
    {
        return $this->status === JournalEntryStatus::DRAFT;
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines->sum('debit_amount');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines->sum('credit_amount');
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.001;
    }
}
