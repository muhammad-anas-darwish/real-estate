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
            'rejection_reason' => ['required_if:status,rejected', 'string', 'max:1000', 'nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
