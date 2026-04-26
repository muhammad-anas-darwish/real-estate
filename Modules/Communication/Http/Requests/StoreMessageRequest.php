<?php

namespace Modules\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Communication\Enums\MessageTypeEnum;

class StoreMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required_if:type,text', 'string', 'max:5000'],
            'type' => ['sometimes', Rule::enum(MessageTypeEnum::class)],
            'parent_id' => ['nullable', 'integer', Rule::exists('messages', 'id')],
            'attachment' => ['sometimes', 'file', 'max:20480'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('type')) {
            $this->merge(['type' => 'text']);
        }
    }
}