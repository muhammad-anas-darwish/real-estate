<?php

namespace Modules\Deposit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
