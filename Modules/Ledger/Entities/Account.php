<?php

namespace Modules\Ledger\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'ledger_accounts';

    protected $fillable = [
        'code',
        'name',
        'type',
        'account_category',
        'account_number',
        'sort_order',
        'description',
        'currency',
        'owner_type',
        'owner_id',
        'parent_id',
        'current_balance',
        'held_balance',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'type' => \Modules\Ledger\Enums\AccountType::class,
        'account_category' => \Modules\Ledger\Enums\AccountCategory::class,
        'current_balance' => 'decimal:2',
        'held_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static $filterableColumns = [
        'type',
        'account_category',
        'currency',
        'is_active',
        'owner_type',
        'parent_id',
    ];

    protected static $searchableColumns = [
        'code',
        'name',
        'account_number',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AccountEntry::class);
    }

    public function getAvailableBalanceAttribute(): float
    {
        return (float) $this->current_balance - (float) $this->held_balance;
    }
}
