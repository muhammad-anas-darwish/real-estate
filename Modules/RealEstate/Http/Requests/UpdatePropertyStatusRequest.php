<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Enums\PropertyStatus;

class UpdatePropertyStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(PropertyStatus::values())],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
