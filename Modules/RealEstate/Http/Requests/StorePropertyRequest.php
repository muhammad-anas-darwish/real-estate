<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Core\TemporaryFile\Entities\TemporaryFile;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;

class StorePropertyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],

            // Location
            'country_id' => ['required', Rule::exists(Country::class, 'id')],
            'city_id' => ['required', Rule::exists(City::class, 'id')->where('country_id', $this->input('country_id'))],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],

            'property_type'    => ['required', Rule::enum(PropertyType::class)],
            'type_of_contract' => ['required', Rule::enum(TypeOfContract::class)],

            // Property Details
            'rooms' => ['required', 'integer', 'min:0'],
            'bathrooms' => ['required', 'integer', 'min:0'],
            'area' => ['required', 'numeric', 'min:0'],

            // Detailed Information
            'detailed_info' => ['nullable', 'string'],

            // Price
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],

            // Main Image
            'main_image' => ['nullable', 'array'],
            'main_image.id' => ['nullable', 'integer'],
            'main_image.temporary_folder' => ['nullable', 'string'],

            // Gallery Images
            'gallery' => ['nullable', 'array'],
            'gallery.*.id' => ['nullable', 'integer'],
            'gallery.*.temporary_folder' => ['nullable', 'string', Rule::exists(TemporaryFile::class, 'folder')->where('type', 'property')],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Auto-set publisher_id from authenticated user if not provided
        if (!$this->has('publisher_id') && auth()->check()) {
            $this->merge([
                'publisher_id' => \Auth::id(),
            ]);
        }

        // Set default currency if not provided
        if (!$this->has('currency')) {
            $this->merge([
                'currency' => 'USD',
            ]);
        }
    }
}
