<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Enums\PropertyDirection;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;

class UpdatePropertyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],

            // Location
            'country_id' => ['sometimes', Rule::exists(Country::class, 'id')],
            'city_id' => ['sometimes', Rule::exists(City::class, 'id')],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],

            'property_type' => ['required', Rule::enum(PropertyType::class)],
            'type_of_contract' => ['required', Rule::enum(TypeOfContract::class)],

            // Property Details
            'rooms' => ['sometimes', 'integer', 'min:0'],
            'bathrooms' => ['sometimes', 'integer', 'min:0'],
            'area' => ['sometimes', 'numeric', 'min:0'],
            'home_directions' => ['nullable', Rule::enum(PropertyDirection::class)],

            // Detailed Information
            'detailed_info' => ['nullable', 'string'],

            // Price
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],

            // Status (only for admins)
            'status' => ['sometimes', Rule::in(PropertyStatus::values())],

            // Main Image
            'main_image' => ['nullable', 'array'],
            'main_image.id' => ['nullable', 'integer'],
            'main_image.temporary_folder' => ['nullable', 'string'],

            // Gallery Images
            'gallery' => ['nullable', 'array'],
            'gallery.*.id' => ['nullable', 'integer'],
            'gallery.*.temporary_folder' => ['nullable', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
