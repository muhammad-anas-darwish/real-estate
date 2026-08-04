<?php

namespace Modules\Core\SubModules\Location\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\SubModules\Location\Entities\Country;

class StoreCityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128'],
            'country_id' => ['required', Rule::exists(Country::class, 'id')],
            'state_provianc' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
