<?php

namespace Modules\Ledger\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ledger\Enums\PayrollPaymentStatus;
use Modules\ServiceProvider\Entities\ServiceProviderProfile;

class PayrollPayment extends BaseModel
{
    use HasFactory;

    protected $table = 'payroll_payments';

    protected $fillable = [
        'payroll_id',
        'service_provider_profile_id',
        'amount',
        'salary_amount',
        'tasks_count',
        'tasks_amount',
        'period_start',
        'period_end',
        'status',
        'paid_at',
        'journal_entry_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'salary_amount' => 'decimal:2',
        'tasks_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'status' => PayrollPaymentStatus::class,
        'paid_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'status',
        'payroll_id',
        'service_provider_profile_id',
    ];

    protected static $dateFilterableColumns = [
        'period_start',
        'period_end',
        'paid_at',
        'created_at',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function serviceProviderProfile(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderProfile::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
