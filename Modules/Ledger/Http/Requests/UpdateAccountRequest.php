<?php

namespace Modules\Ledger\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ledger\Enums\AccountCategory;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountId = $this->route('account')?->id ?? $this->route('id');

        return [
            'code' => ['sometimes', 'required', 'string', 'max:100', 'unique:ledger_accounts,code,'.$accountId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50', 'unique:ledger_accounts,account_number,'.$accountId],
            'account_category' => ['sometimes', 'required', 'string', 'in:'.implode(',', AccountCategory::values())],
            'description' => ['nullable', 'string', 'max:1000'],
            'currency' => ['nullable', 'string', 'max:3'],
            'parent_id' => ['nullable', 'integer', 'exists:ledger_accounts,id', 'not_in:'.$accountId],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
