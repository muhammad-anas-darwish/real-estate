<?php

namespace Modules\Deposit\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\Deposit\Enums\DepositStatus;
use Modules\RealEstate\Entities\Property;

class Deposit extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'deposits';

    protected $fillable = [
        'reference_number',
        'property_id',
        'buyer_id',
        'seller_id',
        'amount',
        'currency',
        'status',
        'terms',
        'notes',
        'idempotency_key',
        'held_at',
        'released_at',
        'refunded_at',
        'cancelled_at',
        'cancelled_by',
        'released_by',
        'refunded_by',
        'cancellation_reason',
        'release_notes',
        'refund_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => DepositStatus::class,
        'held_at' => 'datetime',
        'released_at' => 'datetime',
        'refunded_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'property_id',
        'buyer_id',
        'seller_id',
        'status',
        'currency',
    ];

    protected static $searchableColumns = [
        'reference_number',
        'terms',
        'notes',
    ];

    protected static $dateFilterableColumns = [
        'created_at',
        'held_at',
        'released_at',
        'refunded_at',
        'cancelled_at',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', DepositStatus::PENDING);
    }

    public function scopeHeld($query)
    {
        return $query->where('status', DepositStatus::HELD);
    }

    public function scopeReleased($query)
    {
        return $query->where('status', DepositStatus::RELEASED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', DepositStatus::REFUNDED);
    }

    public function scopeDisputed($query)
    {
        return $query->where('status', DepositStatus::DISPUTED);
    }

    public function scopeForBuyer($query, int $userId)
    {
        return $query->where('buyer_id', $userId);
    }

    public function scopeForSeller($query, int $userId)
    {
        return $query->where('seller_id', $userId);
    }

    protected static function newFactory()
    {
        return \Modules\Deposit\Database\Factories\DepositFactory::new();
    }
}
