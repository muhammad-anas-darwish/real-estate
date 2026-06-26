<?php

namespace Modules\Crm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadNoteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['sometimes', 'required', 'string', 'min:1', 'max:5000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
