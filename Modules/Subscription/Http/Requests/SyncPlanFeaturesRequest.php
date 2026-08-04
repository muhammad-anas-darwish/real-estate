<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPlanFeaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'features' => ['required', 'array'],
            'features.*.feature_id' => ['required', 'integer', Rule::exists('subscription_features', 'id')],
            'features.*.is_enabled' => ['nullable', 'boolean'],
            'features.*.limit_value' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
