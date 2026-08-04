<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Enums\ViewingStatus;

class UpdateViewingStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ViewingStatus::class)],
            'cancellation_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:500'],
            'agent_notes' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['required_if:status,rescheduled', 'nullable', 'date', 'after:now'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
