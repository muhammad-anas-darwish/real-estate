<?php

namespace Modules\Deposit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['nullable', 'integer'],
            'buyer_id' => ['nullable', 'integer'],
            'seller_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:pending,held,released,refunded,disputed,cancelled'],
            'currency' => ['nullable', 'string', 'max:3'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:created_at,amount,held_at,released_at'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
