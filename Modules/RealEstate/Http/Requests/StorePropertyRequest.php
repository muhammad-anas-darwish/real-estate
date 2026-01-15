<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\TemporaryFile\Entities\TemporaryFile;

class StorePropertyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],

            // Location
            'country' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],

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
            'gallery.*.temporary_folder' => ['nullable', 'string', Rule::exists(TemporaryFile::class)->where('type', '')],
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
