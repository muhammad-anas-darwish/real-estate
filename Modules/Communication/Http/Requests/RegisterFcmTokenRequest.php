<?php

namespace Modules\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Communication\Enums\DeviceTypeEnum;

class RegisterFcmTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['string'],
            'device_type' => [Rule::enum(DeviceTypeEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'The token field is required.',
            'device_type.required' => 'The device type field is required.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}