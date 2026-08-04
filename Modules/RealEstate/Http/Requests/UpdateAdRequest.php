<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\AdMediaType;

class UpdateAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'ad_group_id' => ['nullable', Rule::exists(AdGroup::class, 'id')],
            'media_type' => ['sometimes', 'string', Rule::in(AdMediaType::values())],
            'external_url' => ['nullable', 'url'],
            'property_id' => ['nullable', Rule::exists(Property::class, 'id')],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'paused'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'media' => ['nullable', 'array'],
        ];
    }
}
