<?php

namespace Modules\FileSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FileSystem\Enums\StoragePackageType;

class UpgradeStorageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('files.quota');
    }

    public function rules(): array
    {
        return [
            'package_type' => [
                'required', 'string',
                Rule::in(StoragePackageType::values()),
            ],
        ];
    }
}
