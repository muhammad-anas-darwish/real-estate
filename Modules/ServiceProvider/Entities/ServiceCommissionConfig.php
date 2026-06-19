<?php

namespace Modules\ServiceProvider\Entities;

use App\Models\BaseModel;

class ServiceCommissionConfig extends BaseModel
{
    protected $table = 'service_commission_configs';

    protected $fillable = [
        'service_type',
        'commission_type',
        'commission_value',
        'is_active',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static $filterableColumns = [
        'service_type',
        'commission_type',
        'is_active',
    ];

    public static function getCommissionRate(string $serviceType): float
    {
        $config = static::where('service_type', $serviceType)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return 20.0;
        }

        return (float) $config->commission_value;
    }
}
