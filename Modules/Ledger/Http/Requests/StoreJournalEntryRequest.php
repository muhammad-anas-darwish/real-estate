<?php

namespace Modules\Ledger\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:ledger_accounts,id'],
            'lines.*.debit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'At least two entry lines are required.',
            'lines.min' => 'A journal entry must have at least two lines.',
        ];
    }
}
