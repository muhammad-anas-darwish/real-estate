<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Subscription\Enums\DiscountType;

class UpdateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $discountId = $this->route('discount');

        return [
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('subscription_discounts', 'code')->ignore($discountId)],
            'type' => ['sometimes', 'string', Rule::in(DiscountType::values())],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
