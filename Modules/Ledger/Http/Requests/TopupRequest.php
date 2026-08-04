<?php

namespace Modules\Ledger\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:50000'],
            'currency' => ['required', 'string', 'in:usd,sar,aed'],
            'payment_method' => ['required', 'string', 'in:stripe'],
            'description' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
