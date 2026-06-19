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
        'current_balance' => 'decimal:2',
        'held_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static $filterableColumns = [
        'type',
        'currency',
        'is_active',
        'owner_type',
    ];

    protected static $searchableColumns = [
        'code',
        'name',
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
