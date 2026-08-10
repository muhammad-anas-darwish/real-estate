<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EndRentalCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ended_at' => ['nullable', 'date', 'before_or_equal:today'],
            'end_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
