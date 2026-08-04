<?php

namespace Modules\ServiceProvider\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;
use Modules\ServiceProvider\Enums\ServiceRequestStatus;
use Modules\ServiceProvider\Enums\ServiceType;

class ServiceRequest extends BaseModel
{
    protected $table = 'service_requests';

    protected $fillable = [
        'client_id',
        'provider_id',
        'property_id',
        'service_type',
        'status',
        'scheduled_at',
        'completed_at',
        'cancelled_at',
        'client_notes',
        'provider_notes',
        'admin_notes',
        'price',
        'platform_fee',
        'provider_earnings',
        'is_paid',
        'paid_at',
        'is_provider_paid',
    ];

    protected $casts = [
        'service_type' => ServiceType::class,
        'status' => ServiceRequestStatus::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paid_at' => 'datetime',
        'price' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'provider_earnings' => 'decimal:2',
        'is_paid' => 'boolean',
        'is_provider_paid' => 'boolean',
    ];

    protected static $filterableColumns = [
        'service_type',
        'status',
        'client_id',
        'provider_id',
        'property_id',
        'is_paid',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderProfile::class, 'provider_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ServiceRequestTask::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', ServiceRequestStatus::PENDING);
    }

    public function scopeForProvider($query, int $providerId)
    {
        return $query->where(function ($q) use ($providerId) {
            $q->where('provider_id', $providerId)
                ->orWhere(function ($sq) {
                    $sq->whereNull('provider_id')
                        ->where('status', ServiceRequestStatus::PENDING);
                });
        });
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }
}
