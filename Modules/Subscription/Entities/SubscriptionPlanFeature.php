<?php

namespace Modules\Subscription\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPlanFeature extends BaseModel
{
    use HasFactory;
    protected $table = 'subscription_plan_features';

    protected $fillable = [
        'plan_id',
        'feature_id',
        'is_enabled',
        'limit_value',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'limit_value' => 'integer',
    ];

    protected static $filterableColumns = [
        'plan_id',
        'feature_id',
        'is_enabled',
    ];

    protected static $dateFilterableColumns = [
        'created_at',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(SubscriptionFeature::class, 'feature_id');
    }

    protected static function newFactory()
    {
        return \Modules\Subscription\Database\Factories\SubscriptionPlanFeatureFactory::new();
    }
}
