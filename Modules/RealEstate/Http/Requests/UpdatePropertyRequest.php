<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],

            // Location
            'country' => ['sometimes', 'string', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],

            // Property Details
            'rooms' => ['sometimes', 'integer', 'min:0'],
            'bathrooms' => ['sometimes', 'integer', 'min:0'],
            'area' => ['sometimes', 'numeric', 'min:0'],

            // Detailed Information
            'detailed_info' => ['nullable', 'string'],

            // Price
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],

            // Status (only for admins)
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'sold'])],

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
