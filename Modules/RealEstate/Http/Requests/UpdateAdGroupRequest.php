<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $groupId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('ad_groups', 'name')->ignore($groupId)],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'is_archived' => ['sometimes', 'boolean'],
        ];
    }
}
