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

        $this->createPlan('Basic', 'basic', 19.99, 30, 0, [
            'property_listing' => ['is_enabled' => true, 'limit_value' => null],
            'property_images' => ['is_enabled' => true, 'limit_value' => 5],
            'featured_property' => ['is_enabled' => false, 'limit_value' => null],
            'advanced_search' => ['is_enabled' => false, 'limit_value' => null],
            'export_reports' => ['is_enabled' => false, 'limit_value' => null],
            'api_access' => ['is_enabled' => false, 'limit_value' => null],
            'priority_support' => ['is_enabled' => false, 'limit_value' => null],
        ], $features);

        $this->createPlan('Professional', 'professional', 49.99, 30, 1, [
            'property_listing' => ['is_enabled' => true, 'limit_value' => null],
            'property_images' => ['is_enabled' => true, 'limit_value' => 20],
            'featured_property' => ['is_enabled' => true, 'limit_value' => 5],
            'advanced_search' => ['is_enabled' => true, 'limit_value' => null],
            'export_reports' => ['is_enabled' => true, 'limit_value' => null],
            'api_access' => ['is_enabled' => false, 'limit_value' => null],
            'priority_support' => ['is_enabled' => false, 'limit_value' => null],
        ], $features);

        $this->createPlan('Enterprise', 'enterprise', 149.99, 30, 2, [
            'property_listing' => ['is_enabled' => true, 'limit_value' => null],
            'property_images' => ['is_enabled' => true, 'limit_value' => null],
            'featured_property' => ['is_enabled' => true, 'limit_value' => null],
            'advanced_search' => ['is_enabled' => true, 'limit_value' => null],
            'export_reports' => ['is_enabled' => true, 'limit_value' => null],
            'api_access' => ['is_enabled' => true, 'limit_value' => null],
            'priority_support' => ['is_enabled' => true, 'limit_value' => null],
        ], $features);

        $this->command->info('SubscriptionSeeder: plans and features seeded successfully!');
    }

    private function createFeatures(): array
    {
        $featureData = [
            ['property_listing', 'Property Listing', FeatureType::TOGGLE, 'Ability to list properties'],
            ['property_images', 'Property Images', FeatureType::LIMIT, 'Number of images per property'],
            ['featured_property', 'Featured Property', FeatureType::LIMIT, 'Number of featured properties'],
            ['advanced_search', 'Advanced Search', FeatureType::TOGGLE, 'Access to advanced search filters'],
            ['export_reports', 'Export Reports', FeatureType::TOGGLE, 'Ability to export data reports'],
            ['api_access', 'API Access', FeatureType::TOGGLE, 'Access to the REST API'],
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
