<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'group_id' => ['nullable'],
            'ad_id' => ['nullable'],
            'period' => ['nullable', 'string', Rule::in(['day', 'week', 'month'])],
        ];
    }
}
