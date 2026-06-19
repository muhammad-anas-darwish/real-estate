<?php

namespace Modules\ServiceProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type' => ['required', 'string', 'in:photography,inspection,legal,marketing'],
            'provider_id' => ['nullable', 'integer', 'exists:service_provider_profiles,id'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'scheduled_at' => ['nullable', 'date'],
            'client_notes' => ['nullable', 'string', 'max:2000'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
