<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Subscription\Enums\FeatureType;

class UpdateFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $featureId = $this->route('feature');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('subscription_features', 'slug')->ignore($featureId)],
            'type' => ['sometimes', 'string', Rule::in(FeatureType::values())],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
