<?php

namespace Modules\Ledger\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class TopupDTO
{
    public function __construct(
        public float $amount,
        public string $currency,
        public string $paymentMethod, // stripe | balance
        public ?string $description = null,
        public ?string $idempotencyKey = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1', 'max:50000'],
            'currency' => ['required', 'string', 'in:usd,sar,aed'],
            'payment_method' => ['required', 'string', 'in:stripe'],
            'description' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return new self(
            amount: (float) $validated['amount'],
            currency: strtoupper($validated['currency']),
            paymentMethod: $validated['payment_method'],
            description: $validated['description'] ?? null,
            idempotencyKey: $validated['idempotency_key'] ?? null,
        );
    }
}
