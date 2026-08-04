<?php

namespace Modules\Subscription\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Subscription\Enums\DiscountType;

class SubscriptionDiscount extends BaseModel
{
    use HasFactory;

    protected $table = 'subscription_discounts';

    protected $fillable = [
        'code',
        'type',
        'value',
        'plan_id',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'type' => DiscountType::class,
        'value' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static $filterableColumns = [
        'type',
        'is_active',
        'plan_id',
    ];

    protected static $searchableColumns = [
        'code',
    ];

    protected static $dateFilterableColumns = [
        'expires_at',
        'created_at',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'discount_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('plan_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedMaxUses(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    public function isValid(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->hasReachedMaxUses();
    }

    protected static function newFactory()
    {
        return \Modules\Subscription\Database\Factories\SubscriptionDiscountFactory::new();
    }
}
