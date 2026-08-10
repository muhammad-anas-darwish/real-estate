<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTextFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.edit');
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5242880'],
        ];
    }
}
