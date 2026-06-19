<?php

namespace Modules\Subscription\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionStatusLog extends BaseModel
{
    use HasFactory;

    protected $table = 'subscription_status_logs';

    protected $fillable = [
        'subscription_id',
        'from_status',
        'to_status',
        'reason',
    ];

    protected $casts = [
        'from_status' => 'string',
        'to_status' => 'string',
    ];

    protected static $filterableColumns = [
        'from_status',
        'to_status',
        'subscription_id',
    ];

    protected static $dateFilterableColumns = [
        'created_at',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    protected static function newFactory()
    {
        return \Modules\Subscription\Database\Factories\SubscriptionStatusLogFactory::new();
    }
}
