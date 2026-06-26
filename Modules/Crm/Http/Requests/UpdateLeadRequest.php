<?php

namespace Modules\Crm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Crm\Enums\LeadSource;

class UpdateLeadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'phone' => ['sometimes', 'required', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'email', 'max:120'],
            'source' => ['sometimes', 'required', 'string', 'in:'.implode(',', array_column(LeadSource::cases(), 'value'))],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
