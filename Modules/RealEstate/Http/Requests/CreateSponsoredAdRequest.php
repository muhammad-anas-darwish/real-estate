<?php

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\AdMediaType;
use Modules\RealEstate\Enums\PricingTier;
use Modules\RealEstate\Enums\SponsorDuration;

class CreateSponsoredAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'media_type' => ['required', 'string', Rule::in(AdMediaType::values())],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'property_id' => ['nullable', Rule::exists(Property::class, 'id')],
            'pricing_tier' => ['required', 'string', Rule::in(PricingTier::values())],
            'sponsor_duration' => ['required', 'string', Rule::in(SponsorDuration::values())],
            'payment_method' => ['required', 'string', Rule::in(['stripe', 'balance'])],
            'currency' => ['required', 'string', Rule::in(['usd', 'sar', 'aed'])],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'media' => ['nullable', 'array'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:20480'],
        ];
    }
}
