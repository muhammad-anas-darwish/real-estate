<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_enabled' => ['sometimes', 'boolean'],
            'limit_value' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
