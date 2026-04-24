<?php

namespace Modules\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Communication\Entities\Conversation;

class StoreMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'conversation_id' => ['required', Rule::exists(Conversation::class, 'id')],
            'content' => ['required', 'string', 'max:5000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}