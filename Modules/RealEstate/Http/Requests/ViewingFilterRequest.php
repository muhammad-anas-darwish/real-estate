<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Enums\ViewingStatus;
use Modules\RealEstate\Enums\ViewingType;

class ViewingFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'property_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'agent_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(ViewingStatus::class)],
            'viewing_type' => ['nullable', Rule::enum(ViewingType::class)],
            'scheduled_at.from' => ['nullable', 'date'],
            'scheduled_at.to' => ['nullable', 'date', 'after_or_equal:scheduled_at.from'],
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', Rule::in(['scheduled_at', 'created_at', 'status'])],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
