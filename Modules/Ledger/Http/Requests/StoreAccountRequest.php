<?php

namespace Modules\Ledger\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ledger\Enums\AccountCategory;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'unique:ledger_accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50', 'unique:ledger_accounts,account_number'],
            'account_category' => ['required', 'string', 'in:'.implode(',', AccountCategory::values())],
            'description' => ['nullable', 'string', 'max:1000'],
            'currency' => ['nullable', 'string', 'max:3'],
            'parent_id' => ['nullable', 'integer', 'exists:ledger_accounts,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
