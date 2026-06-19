<?php

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Subscription\Entities\SubscriptionFeature;
use Modules\Subscription\Entities\SubscriptionPlan;
use Modules\Subscription\Enums\FeatureType;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $features = $this->createFeatures();

        // Individual Plans
        $this->createPlan('Individual Basic', 'individual-basic', 9.99, 30, 0, 'individual', [
            'property_listing' => ['is_enabled' => true, 'limit_value' => null],
            'property_listing_limit' => ['is_enabled' => true, 'limit_value' => 5],
            'property_images' => ['is_enabled' => true, 'limit_value' => 10],
            'featured_property' => ['is_enabled' => false, 'limit_value' => null],
            'advanced_analytics' => ['is_enabled' => false, 'limit_value' => null],
            'verified_badge' => ['is_enabled' => false, 'limit_value' => null],
            'external_contact' => ['is_enabled' => false, 'limit_value' => null],
            'priority_support' => ['is_enabled' => false, 'limit_value' => null],
        ], $features);

        $this->createPlan('Individual Pro', 'individual-pro', 29.99, 30, 1, 'individual', [
            'property_listing' => ['is_enabled' => true, 'limit_value' => null],
            'property_listing_limit' => ['is_enabled' => true, 'limit_value' => 15],
            'property_images' => ['is_enabled' => true, 'limit_value' => 20],
            'featured_property' => ['is_enabled' => true, 'limit_value' => 3],
            'advanced_analytics' => ['is_enabled' => false, 'limit_value' => null],
            'verified_badge' => ['is_enabled' => false, 'limit_value' => null],
            'external_contact' => ['is_enabled' => true, 'limit_value' => null],
            'priority_support' => ['is_enabled' => false, 'limit_value' => null],
        ], $features);

        // Office Plans
        $this->createPlan('Office Basic', 'office-basic', 39.99, 30, 2, 'office', [
            'property_listing' => ['is_enabled' => true, 'limit_value' => null],
            'property_listing_limit' => ['is_enabled' => true, 'limit_value' => 25],
            'property_images' => ['is_enabled' => true, 'limit_value' => 20],
            'featured_property' => ['is_enabled' => true, 'limit_value' => 5],
            'advanced_analytics' => ['is_enabled' => false, 'limit_value' => null],
            'verified_badge' => ['is_enabled' => false, 'limit_value' => null],
            'external_contact' => ['is_enabled' => true, 'limit_value' => null],
            'priority_support' => ['is_enabled' => false, 'limit_value' => null],
        ], $features);

        $this->createPlan('Office Pro', 'office-pro', 99.99, 30, 3, 'office', [
            'property_listing' => ['is_enabled' => true, 'limit_value' => null],
            'property_listing_limit' => ['is_enabled' => true, 'limit_value' => 100],
            'property_images' => ['is_enabled' => true, 'limit_value' => 50],
            'featured_property' => ['is_enabled' => true, 'limit_value' => 15],
            'advanced_analytics' => ['is_enabled' => true, 'limit_value' => null],
            'verified_badge' => ['is_enabled' => true, 'limit_value' => null],
            'external_contact' => ['is_enabled' => true, 'limit_value' => null],
            'priority_support' => ['is_enabled' => false, 'limit_value' => null],
        ], $features);

        $this->createPlan('Office Enterprise', 'office-enterprise', 199.99, 30, 4, 'office', [
            'property_listing' => ['is_enabled' => true, 'limit_value' => null],
            'property_listing_limit' => ['is_enabled' => true, 'limit_value' => null],
            'property_images' => ['is_enabled' => true, 'limit_value' => null],
            'featured_property' => ['is_enabled' => true, 'limit_value' => null],
            'advanced_analytics' => ['is_enabled' => true, 'limit_value' => null],
            'verified_badge' => ['is_enabled' => true, 'limit_value' => null],
            'external_contact' => ['is_enabled' => true, 'limit_value' => null],
            'priority_support' => ['is_enabled' => true, 'limit_value' => null],
        ], $features);

        $this->command->info('SubscriptionSeeder: plans and features seeded successfully!');
    }

    private function createFeatures(): array
    {
        $featureData = [
            ['property_listing', 'Property Listing', FeatureType::TOGGLE, 'Ability to list properties'],
            ['property_listing_limit', 'Property Listing Limit', FeatureType::LIMIT, 'Maximum number of active properties'],
            ['property_images', 'Property Images', FeatureType::LIMIT, 'Number of images per property'],
            ['featured_property', 'Featured Property', FeatureType::LIMIT, 'Number of featured properties'],
            ['advanced_analytics', 'Advanced Analytics', FeatureType::TOGGLE, 'Access to advanced analytics and reports'],
            ['verified_badge', 'Verified Badge', FeatureType::TOGGLE, 'Verified office badge display'],
            ['external_contact', 'External Contact', FeatureType::TOGGLE, 'Show external contact methods'],
            ['priority_support', 'Priority Support', FeatureType::TOGGLE, 'Priority customer support'],
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

    private function createPlan(
        string $name,
        string $slug,
        float $price,
        int $durationDays,
        int $sortOrder,
        ?string $publisherType,
        array $planFeatures,
        array $features
    ): void {
        $plan = SubscriptionPlan::firstOrCreate(
            ['slug' => $slug],
            compact('name', 'slug', 'price') + [
                'description' => "The {$name} plan for real estate professionals.",
                'currency' => 'USD',
                'duration_days' => $durationDays,
                'sort_order' => $sortOrder,
                'is_active' => true,
                'publisher_type' => $publisherType,
            ]
        );

        $syncData = [];
        foreach ($planFeatures as $slug => $config) {
            if (isset($features[$slug])) {
                $syncData[$features[$slug]->id] = [
                    'is_enabled' => $config['is_enabled'],
                    'limit_value' => $config['limit_value'],
                ];
            }
        }

        $plan->features()->sync($syncData);
    }
}
