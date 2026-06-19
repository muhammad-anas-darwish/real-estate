<?php

namespace Modules\Ledger\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ledger\Enums\PayrollType;
use Modules\ServiceProvider\Entities\ServiceProviderProfile;

class Payroll extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'payrolls';

    protected $fillable = [
        'service_provider_profile_id',
        'type',
        'base_salary',
        'per_task_rate',
        'is_active',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'type' => PayrollType::class,
        'base_salary' => 'decimal:2',
        'per_task_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static $filterableColumns = [
        'type',
        'is_active',
        'service_provider_profile_id',
    ];

    public function serviceProviderProfile(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderProfile::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class)->orderByDesc('period_start');
    }
}
