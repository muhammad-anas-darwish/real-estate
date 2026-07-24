<?php

namespace Modules\Deposit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'in:balance,stripe'],
        ];
    }
}
