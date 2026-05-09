<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;

class AdvancedPropertyFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Price range
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],

            // Area range
            'area_min' => ['nullable', 'numeric', 'min:0'],
            'area_max' => ['nullable', 'numeric', 'min:0'],

            // Rooms range
            'rooms_min' => ['nullable', 'integer', 'min:0'],
            'rooms_max' => ['nullable', 'integer', 'min:0'],

            // Bathrooms range
            'bathrooms_min' => ['nullable', 'integer', 'min:0'],
            'bathrooms_max' => ['nullable', 'integer', 'min:0'],

            // Exact match filters
            'property_type' => ['nullable', 'string', Rule::enum(PropertyType::class)],
            'type_of_contract' => ['nullable', 'string', Rule::enum(TypeOfContract::class)],
            'country_id' => ['nullable', 'integer', Rule::exists(Country::class, 'id')],
            'city_id' => ['nullable', 'integer', Rule::exists(City::class, 'id')],

            // Text search
            'search' => ['nullable', 'string', 'max:255'],

            // Sorting
            'sort_by' => ['nullable', 'string', Rule::in(['price', 'area', 'rooms', 'bathrooms', 'created_at', 'views'])],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],

            // Pagination
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
