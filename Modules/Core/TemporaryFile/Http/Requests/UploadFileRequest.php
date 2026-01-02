<?php

namespace Modules\Core\TemporaryFile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\TemporaryFile\Entities\TemporaryFile;

class UploadFileRequest extends FormRequest
{
    public function rules(): array
    {
        $type = $this->input('type');

        if (!$type || !array_key_exists($type, TemporaryFile::$rules)) {
            throw ValidationException::withMessages([
                'type' => [__('The selected type is invalid.')],
            ]);
        }

        $rules = TemporaryFile::getRules($type);

        $maxSize = $rules['max_size'] ?? 20480;
        $extensions = $rules['extensions'] ?? [
            'jpg',
            'png',
            'pdf',
            'docx',
            'doc',
            'xls',
            'xlsx',
            'csv',
            'txt',
            'gif',
            'bmp',
            'tiff',
        ];

        $mimes = implode(',', $extensions);

        return [
            'type' => ['required', Rule::in(array_keys(TemporaryFile::$rules))],

            'file' => "required_without:files|file|mimes:$mimes|max:$maxSize",

            'files' => 'required_without:file|array',
            'files.*' => "required|file|mimes:$mimes|max:$maxSize",
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
