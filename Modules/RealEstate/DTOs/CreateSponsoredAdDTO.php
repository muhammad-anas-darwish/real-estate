<?php

namespace Modules\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\RealEstate\Enums\PricingTier;
use Modules\RealEstate\Enums\SponsorDuration;

final readonly class CreateSponsoredAdDTO
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $mediaType,
        public ?string $externalUrl,
        public ?int $propertyId,
        public PricingTier $pricingTier,
        public SponsorDuration $sponsorDuration,
        public float $amountPaid,
        public string $currency,
        public string $paymentMethod,
        public ?array $media = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $mediaItemRules = $request->input('media_type') === 'video'
            ? ['mimes:mp4,mov,webm', 'max:102400']
            : ['mimes:jpg,jpeg,png,webp', 'max:5120'];

        $tier = PricingTier::tryFrom($request->input('pricing_tier', 'basic'));
        $duration = SponsorDuration::tryFrom($request->input('sponsor_duration', '7_days'));

        if (! $tier) {
            throw ValidationException::withMessages(['pricing_tier' => 'Invalid pricing tier.']);
        }

        if (! $duration) {
            throw ValidationException::withMessages(['sponsor_duration' => 'Invalid sponsor duration.']);
        }

        $prices = $tier->defaultPrices();
        $amountPaid = $prices[$duration->value] ?? 0;

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'media_type' => ['required', 'string', 'in:video,image'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'pricing_tier' => ['required', 'string', 'in:basic,standard,premium'],
            'sponsor_duration' => ['required', 'string', 'in:7_days,14_days,30_days'],
            'payment_method' => ['required', 'string', 'in:stripe,balance'],
            'currency' => ['required', 'string', 'in:usd,sar,aed'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'media' => ['nullable', 'array'],
            'media.*' => $mediaItemRules,
        ];

        if ($request->input('media_type') === 'image') {
            $rules['media'][] = 'max:10';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $media = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $media[] = $file;
            }
        } elseif (! empty($validated['media'])) {
            $media = $validated['media'];
        }

        return new self(
            title: $validated['title'],
            description: $validated['description'] ?? null,
            mediaType: $validated['media_type'],
            externalUrl: $validated['external_url'] ?? null,
            propertyId: $validated['property_id'] ?? null,
            pricingTier: $tier,
            sponsorDuration: $duration,
            amountPaid: $amountPaid,
            currency: strtoupper($validated['currency']),
            paymentMethod: $validated['payment_method'],
            media: $media,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'media_type' => $this->mediaType,
            'external_url' => $this->externalUrl,
            'property_id' => $this->propertyId,
            'pricing_tier' => $this->pricingTier->value,
            'sponsor_duration' => $this->sponsorDuration->value,
            'amount_paid' => $this->amountPaid,
            'currency' => $this->currency,
            'payment_method' => $this->paymentMethod,
        ], fn ($value) => $value !== null);
    }

    public function idempotencyKey(): ?string
    {
        return request('idempotency_key');
    }
}
