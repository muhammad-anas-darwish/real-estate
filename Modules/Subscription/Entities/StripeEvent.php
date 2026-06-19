<?php

namespace Modules\Subscription\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StripeEvent extends BaseModel
{
    use HasFactory;

    protected $table = 'stripe_events';

    protected $fillable = [
        'stripe_event_id',
        'type',
        'status',
        'payload',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected static $filterableColumns = [
        'status',
        'type',
    ];

    protected static $searchableColumns = [
        'stripe_event_id',
    ];

    protected static $dateFilterableColumns = [
        'created_at',
    ];

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    protected static function newFactory()
    {
        return \Modules\Subscription\Database\Factories\StripeEventFactory::new();
    }
}
