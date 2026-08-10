<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.move');
    }

    public function rules(): array
    {
        return [
            'target_folder_id' => [
                'required', 'integer',
                Rule::exists('user_folders', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
