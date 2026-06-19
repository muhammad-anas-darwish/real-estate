<?php

namespace Modules\Ledger\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class PayrollPaymentResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'payroll_id' => $this->payroll_id,
            'service_provider_profile_id' => $this->service_provider_profile_id,
            'amount' => (float) $this->amount,
            'salary_amount' => (float) $this->salary_amount,
            'tasks_count' => $this->tasks_count,
            'tasks_amount' => (float) $this->tasks_amount,
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'journal_entry_id' => $this->journal_entry_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'payroll' => PayrollResource::class,
            'journalEntry' => JournalEntryResource::class,
            'serviceProviderProfile' => \Modules\ServiceProvider\Http\Resources\ServiceProviderResource::class,
        ];
    }
}
