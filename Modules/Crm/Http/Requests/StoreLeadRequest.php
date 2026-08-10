<?php

namespace Modules\Crm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Crm\Enums\LeadSource;

class StoreLeadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'source' => ['required', 'string', 'in:'.implode(',', array_column(LeadSource::cases(), 'value'))],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
