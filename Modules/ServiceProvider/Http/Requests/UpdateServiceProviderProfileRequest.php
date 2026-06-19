<?php

namespace Modules\ServiceProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:photographer,lawyer,inspector,marketer,other'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'price_type' => ['nullable', 'string', 'in:fixed,hourly,negotiable'],
            'price_per_task' => ['nullable', 'numeric', 'min:0'],
            'coverage_city_ids' => ['nullable', 'array'],
            'coverage_city_ids.*' => ['integer', 'exists:cities,id'],
            'license_document' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
