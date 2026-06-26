<?php

namespace Modules\Crm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Crm\Enums\LeadStatus;

class ChangeLeadStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', array_column(LeadStatus::cases(), 'value'))],
            'lost_reason' => ['required_if:status,lost', 'nullable', 'string', 'max:500'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
