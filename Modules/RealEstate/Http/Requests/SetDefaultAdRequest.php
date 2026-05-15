<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Entities\Ad;

class SetDefaultAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ad_id' => ['required', Rule::exists(Ad::class, 'id')],
            'ad_group_id' => ['required'],
        ];
    }
}
