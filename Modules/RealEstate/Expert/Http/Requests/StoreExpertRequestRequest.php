<?php

namespace Modules\RealEstate\Expert\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Expert\Enums\ExpertType;

class StoreExpertRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'expert_type' => ['required', Rule::enum(ExpertType::class)],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
