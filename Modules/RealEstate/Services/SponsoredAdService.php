<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\User;
use Modules\Core\TemporaryFile\Services\MediaSyncService;
use Modules\Ledger\Enums\AccountType;
use Modules\Ledger\Services\AccountService;
use Modules\Ledger\Services\LedgerService;
use Modules\RealEstate\DTOs\CreateSponsoredAdDTO;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\AdType;
use Modules\RealEstate\Enums\PricingTier;
use Modules\RealEstate\Enums\SponsorDuration;
use Stripe\StripeClient;

class SponsoredAdService extends BaseService
{
    const CACHE_TAG = 'sponsored_ads';

    public function __construct(
        protected MediaSyncService $mediaSyncService,
        protected LedgerService $ledgerService,
        protected AccountService $accountService,
    ) {}

    public function getForUser(int $userId): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Ad::where('user_id', $userId)
            ->where('type', AdType::SPONSORED)
            ->with(['user', 'media', 'property'])
            ->orderByDesc('created_at')
            ->paginate($this->getPerPage());
    }

    public function create(CreateSponsoredAdDTO $dto, User $user): Ad
    {
        return DB::transaction(function () use ($dto, $user): Ad {
            $now = now();
            $startsAt = $now;
            $endsAt = $now->copy()->addDays($dto->sponsorDuration->days());

            $ad = Ad::create([
                'type' => AdType::SPONSORED,
                'title' => $dto->title,
                'description' => $dto->description,
                'media_type' => $dto->mediaType,
                'external_url' => $dto->externalUrl,
                'property_id' => $dto->propertyId,
                'status' => AdStatus::ACTIVE,
                'created_by' => $user->id,
                'user_id' => $user->id,
                'amount_paid' => $dto->amountPaid,
                'currency' => $dto->currency,
                'payment_method' => $dto->paymentMethod,
                'pricing_tier' => $dto->pricingTier->value,
                'sponsor_duration' => $dto->sponsorDuration->value,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $idempotencyKey = $dto->idempotencyKey() ?? 'ad_'.$ad->id.'_'.\Illuminate\Support\Str::uuid();

            if ($dto->paymentMethod === 'balance') {
                $this->payFromBalance($user, $ad, $dto, $idempotencyKey);
            } else {
                $this->payViaStripe($user, $ad, $dto, $idempotencyKey);
            }

            if (! empty($dto->media)) {
                $this->mediaSyncService->syncFiles(
                    model: $ad,
                    files: $dto->media,
                    ruleName: 'ad_media',
                    collectionName: 'ad_media'
                );
            }

            return $ad->fresh(['user', 'media', 'property']);
        });
    }

    public function cancel(Ad $ad, User $user): Ad
    {
        if ($ad->user_id !== $user->id) {
            throw new \RuntimeException('You can only cancel your own ads.');
        }

        if (! in_array($ad->status, [AdStatus::ACTIVE, AdStatus::DRAFT])) {
            throw new \RuntimeException('Only active or draft ads can be cancelled.');
        }

        $ad->update([
            'status' => AdStatus::ARCHIVED,
            'ends_at' => now(),
        ]);

        return $ad;
    }

    public function getPricing(): array
    {
        $result = [];
        foreach (PricingTier::cases() as $tier) {
            $durations = [];
            foreach ($tier->defaultPrices() as $durationKey => $price) {
                $duration = SponsorDuration::from($durationKey);
                $durations[] = [
                    'duration' => $duration->value,
                    'days' => $duration->days(),
                    'label' => $duration->label(),
                    'price' => $price,
                ];
            }

            $result[] = [
                'tier' => $tier->value,
                'label' => $tier->label(),
                'rotation_weight' => $tier->rotationWeight(),
                'durations' => $durations,
            ];
        }

        return $result;
    }

    public function getPricingForDuration(SponsorDuration $duration, PricingTier $tier): float
    {
        return $tier->defaultPrices()[$duration->value] ?? 0;
    }

    private function payFromBalance(User $user, Ad $ad, CreateSponsoredAdDTO $dto, string $idempotencyKey): void
    {
        $userAccount = $this->accountService->getUserAccount($user, $dto->currency);
        $revenueAccount = $this->accountService->getSystemAccount(AccountType::REVENUE, $dto->currency);

        $result = $this->ledgerService->transfer(
            from: $userAccount,
            to: $revenueAccount,
            amount: $dto->amountPaid,
            currency: $dto->currency,
            referenceType: 'ad_payment',
            referenceId: $ad->id,
            description: "Sponsored ad: {$ad->title} ({$dto->pricingTier->label()} / {$dto->sponsorDuration->label()})",
            idempotencyKey: $idempotencyKey,
        );

        $ad->update(['payment_reference' => $result['batch_id']]);
    }

    private function payViaStripe(User $user, Ad $ad, CreateSponsoredAdDTO $dto, string $idempotencyKey): void
    {
        $stripe = new StripeClient(config('subscription.secret_key'));

        // Create PaymentIntent
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => (int) round($dto->amountPaid * 100),
            'currency' => strtolower($dto->currency),
            'metadata' => [
                'user_id' => (string) $user->id,
                'ad_id' => (string) $ad->id,
                'type' => 'sponsored_ad',
                'pricing_tier' => $dto->pricingTier->value,
                'sponsor_duration' => $dto->sponsorDuration->value,
                'idempotency_key' => $idempotencyKey,
            ],
        ], [
            'idempotency_key' => $idempotencyKey,
        ]);

        $ad->update([
            'status' => AdStatus::DRAFT,
            'payment_reference' => $paymentIntent->id,
        ]);

        // The webhook handler will activate the ad when payment succeeds
        // and create the ledger entries
    }

    private function getStripeClient(): StripeClient
    {
        return new StripeClient(config('subscription.secret_key'));
    }
}
