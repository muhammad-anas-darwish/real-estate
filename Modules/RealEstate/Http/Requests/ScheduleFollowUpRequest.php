<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Enums\ContactMethod;

class ScheduleFollowUpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'followable_id' => ['required', 'integer'],
            'followable_type' => ['required', 'string', 'max:255'],
            'agent_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'contact_method' => ['nullable', Rule::enum(ContactMethod::class)],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
