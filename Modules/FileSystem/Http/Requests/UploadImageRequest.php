<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadImageRequest extends FormRequest
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
            'image' => ['required', 'file', 'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:10240',
            ],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
