<?php

namespace Modules\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Communication\Enums\ConversationType;
use Modules\RealEstate\Entities\Property;

class StoreConversationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'property_id' => ['nullable', Rule::exists(Property::class, 'id')],
            'type' => ['nullable', Rule::enum(ConversationType::class)],
            'recipient_id' => ['required', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
