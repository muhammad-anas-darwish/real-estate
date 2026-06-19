<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Subscription\Enums\DiscountType;

class StoreDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('subscription_discounts', 'code')],
            'type' => ['required', 'string', Rule::in(DiscountType::values())],
            'value' => ['required', 'numeric', 'min:0'],
            'plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
