<?php

namespace Modules\ServiceProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checklist_json' => ['nullable', 'array'],
        ];
    }
}
