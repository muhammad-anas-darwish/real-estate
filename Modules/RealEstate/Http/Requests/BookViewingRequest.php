<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\ViewingType;

class BookViewingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'property_id' => ['required', Rule::exists(Property::class, 'id')],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:60'],
            'viewing_type' => ['nullable', Rule::enum(ViewingType::class)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'max_attendees' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('user_id') && auth()->check()) {
            $this->merge([
                'user_id' => auth()->id(),
            ]);
        }
    }
}
