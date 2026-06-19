<?php

namespace Modules\Subscription\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Subscription\Enums\FeatureType;

class SubscriptionFeature extends BaseModel
{
    use HasFactory;

    protected $table = 'subscription_features';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
    ];

    protected $casts = [
        'type' => FeatureType::class,
    ];

    protected static $filterableColumns = [
        'type',
    ];

    protected static $searchableColumns = [
        'name',
    ];

    protected static $dateFilterableColumns = [
        'created_at',
    ];

    public function plans()
    {
        return $this->belongsToMany(
            SubscriptionPlan::class,
            'subscription_plan_features',
            'feature_id',
            'plan_id'
        )->withPivot(['is_enabled', 'limit_value'])->withTimestamps();
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(SubscriptionPlanFeature::class, 'feature_id');
    }

    public function scopeToggle($query)
    {
        return $query->where('type', FeatureType::TOGGLE);
    }

    public function scopeLimit($query)
    {
        return $query->where('type', FeatureType::LIMIT);
    }

    protected static function newFactory()
    {
        return \Modules\Subscription\Database\Factories\SubscriptionFeatureFactory::new();
    }
}
