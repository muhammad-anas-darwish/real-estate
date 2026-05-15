<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Enums\AdStatus;
use Tests\TestCase;

class AdDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_available_ads()
    {
        AdGroup::factory()->create(['status' => 'active', 'is_archived' => false]);
        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'ad_group_id' => 1,
        ]);

        $response = $this->getJson('/api/ads/display');

        $response->assertStatus(200);
    }

    public function test_can_get_standalone_ads()
    {
        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'ad_group_id' => null,
        ]);

        $response = $this->getJson('/api/ads/display/standalone');

        $response->assertStatus(200);
    }

    public function test_can_get_ads_for_group()
    {
        $group = AdGroup::factory()->create(['status' => 'active', 'is_archived' => false]);
        Ad::factory()->create([
            'ad_group_id' => $group->id,
            'status' => AdStatus::ACTIVE,
            'start_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $response = $this->getJson("/api/ads/display/{$group->id}");

        $response->assertStatus(200);
    }

    public function test_non_existent_group_returns_no_ads()
    {
        $response = $this->getJson('/api/ads/display/99999');

        $response->assertStatus(200);
    }

    public function test_standalone_ads_exclude_inactive()
    {
        Ad::factory()->create([
            'status' => AdStatus::DRAFT,
            'ad_group_id' => null,
            'start_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $response = $this->getJson('/api/ads/display/standalone');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    public function test_ads_outside_date_range_not_displayed()
    {
        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'ad_group_id' => null,
            'start_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response = $this->getJson('/api/ads/display/standalone');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }
}
