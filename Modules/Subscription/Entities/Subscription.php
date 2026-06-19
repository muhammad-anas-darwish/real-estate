<?php

namespace Modules\Subscription\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\Subscription\Enums\SubscriptionStatus;

class Subscription extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'discount_id',
        'stripe_checkout_session_id',
        'currency',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'status',
        'plan_id',
        'user_id',
    ];

    protected static $multiFilterableColumns = [
        'id',
        'status',
    ];

    protected static $dateFilterableColumns = [
        'starts_at',
        'ends_at',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(SubscriptionDiscount::class, 'discount_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SubscriptionStatusLog::class, 'subscription_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', SubscriptionStatus::PENDING);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', SubscriptionStatus::EXPIRED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', SubscriptionStatus::CANCELLED);
    }

    protected static function newFactory()
    {
        return \Modules\Subscription\Database\Factories\SubscriptionFactory::new();
    }
}
