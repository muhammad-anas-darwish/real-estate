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
            'token' => ['required', 'string'],
            'device_type' => ['required', Rule::enum(DeviceTypeEnum::class)],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->check();
    }
}