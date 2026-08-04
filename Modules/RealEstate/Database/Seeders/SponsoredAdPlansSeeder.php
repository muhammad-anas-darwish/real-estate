<?php

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RealEstate\Enums\PricingTier;
use Modules\Subscription\Entities\SubscriptionFeature;
use Modules\Subscription\Enums\FeatureType;

class SponsoredAdPlansSeeder extends Seeder
{
    /**
     * This seeder documents the sponsored ad pricing tiers and associated
     * subscription features. It does NOT create full subscription plans —
     * those are defined in Modules/Subscription/Database/Seeders/SubscriptionSeeder.php
     *
     * Feature slugs available for sponsored ads:
     *   - sponsored_ads_basic (Toggle): Can create basic sponsored ads
     *   - sponsored_ads_standard (Toggle): Can create standard sponsored ads
     *   - sponsored_ads_premium (Toggle): Can create premium sponsored ads
     *   - sponsored_ads_limit (Limit): Max active sponsored ads
     *   - sponsored_ads_discount_pct (Limit): Discount % on sponsored ad prices
     */
    public function run(): void
    {
        $features = $this->createFeatures();

        $this->command->info('SponsoredAdPlansSeeder: features seeded.');

        $this->command->table(
            ['Tier', '7 Days', '14 Days', '30 Days', 'Rotation Weight'],
            array_map(fn (PricingTier $tier) => [
                $tier->label(),
                '$'.number_format($tier->defaultPrices()['7_days'], 2),
                '$'.number_format($tier->defaultPrices()['14_days'], 2),
                '$'.number_format($tier->defaultPrices()['30_days'], 2),
                $tier->rotationWeight(),
            ], PricingTier::cases())
        );
    }

    private function createFeatures(): array
    {
        $featureData = [
            ['sponsored_ads', 'Sponsored Ads', FeatureType::TOGGLE, 'Ability to create paid sponsored ads'],
            ['sponsored_ads_limit', 'Sponsored Ads Limit', FeatureType::LIMIT, 'Maximum number of active sponsored ads'],
            ['sponsored_ads_discount_pct', 'Sponsored Ads Discount %', FeatureType::LIMIT, 'Percentage discount on sponsored ad prices'],
        ];

        $features = [];

        foreach ($featureData as [$slug, $name, $type, $description]) {
            $features[$slug] = SubscriptionFeature::firstOrCreate(
                ['slug' => $slug],
                compact('slug', 'name', 'type', 'description')
            );
        }

        return $features;
    }
}
