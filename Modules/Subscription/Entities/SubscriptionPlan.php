<?php

namespace Modules\Subscription\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'duration_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'duration_days' => 'integer',
    ];

    protected static $filterableColumns = [
        'is_active',
        'currency',
    ];

    protected static $searchableColumns = [
        'name',
        'description',
    ];

    protected static $dateFilterableColumns = [
        'created_at',
    ];

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            SubscriptionFeature::class,
            'subscription_plan_features',
            'plan_id',
            'feature_id'
        )->withPivot(['is_enabled', 'limit_value'])->withTimestamps();
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(SubscriptionPlanFeature::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(SubscriptionDiscount::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    protected static function newFactory()
    {
        return \Modules\Subscription\Database\Factories\SubscriptionPlanFactory::new();
    }
}
