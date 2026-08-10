<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\PropertyStatus;

class CreateRentalCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', RentalCard::class);
    }

    public function rules(): array
    {
        return [
            'property_id' => [
                'required', 'integer',
                Rule::exists('properties', 'id')->where(fn ($q) => $q
                    ->where('publisher_id', $this->user()->id)
                    ->where('status', PropertyStatus::APPROVED->value)),
            ],
            'tenant_user_id' => ['nullable', 'integer', Rule::exists((new User)->getTable(), 'id')],
            'external_tenant_name' => ['nullable', 'string', 'max:255', 'required_without:tenant_user_id'],
            'external_tenant_phone' => ['nullable', 'string', 'max:32'],
            'external_tenant_email' => ['nullable', 'email', 'max:255'],
            'external_tenant_id_notes' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_renewable' => ['nullable', 'boolean'],
            'pre_rental_photos' => ['nullable', 'array', 'max:20'],
            'pre_rental_photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
