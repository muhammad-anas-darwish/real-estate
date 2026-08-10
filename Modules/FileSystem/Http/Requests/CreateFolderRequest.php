<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.create');
    }

    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('user_folders', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
