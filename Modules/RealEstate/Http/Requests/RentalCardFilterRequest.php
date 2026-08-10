<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Enums\RentalCardStatus;

class RentalCardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['nullable', 'integer'],
            'owner_id' => ['nullable', 'integer'],
            'tenant_user_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', Rule::in(RentalCardStatus::values())],
            'is_renewable' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', Rule::in(['created_at', 'start_date', 'end_date'])],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
