<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTextFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.create');
    }

    public function rules(): array
    {
        return [
            'folder_id' => [
                'required', 'integer',
                Rule::exists('user_folders', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5242880'],
        ];
    }
}
