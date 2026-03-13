<?php

namespace Modules\Core\SubModules\Location\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCountryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128'],
            'code' => ['nullable', 'string', 'max:3'],
            'phone_code' => ['nullable', 'string', 'max:4'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
