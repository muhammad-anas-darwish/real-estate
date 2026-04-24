<?php

namespace Modules\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChatRoomRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['private', 'property'])],
            'recipient_id' => ['required_if:type,private', 'integer'],
            'property_id' => ['required_if:type,property', 'integer', Rule::exists('properties', 'id')],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}