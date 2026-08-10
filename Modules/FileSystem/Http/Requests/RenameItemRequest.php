<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenameItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.rename');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
