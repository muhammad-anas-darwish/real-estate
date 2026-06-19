<?php

namespace Modules\Ledger\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ledger\Enums\PayrollType;

class UpdatePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', PayrollType::values())],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'per_task_rate' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
