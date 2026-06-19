<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Tests\TestCase;

class AdTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        $group = AdGroup::factory()->create();
        $this->ad = Ad::factory()->create([
            'ad_group_id' => $group->id,
            'status' => 'active',
        ]);
    }

    public function test_can_record_ad_view()
    {
        $response = $this->postJson("/api/public/ads/{$this->ad->id}/track/view");

        $response->assertStatus(200);
        $this->assertDatabaseHas('ad_views', [
            'ad_id' => $this->ad->id,
        ]);
    }

    public function test_can_record_ad_visit()
    {
        $ad = Ad::factory()->create([
            'ad_group_id' => $this->ad->ad_group_id,
            'external_url' => 'https://example.com',
        ]);

        $response = $this->postJson("/api/public/ads/{$ad->id}/track/visit");

        $response->assertStatus(200);
        $this->assertDatabaseHas('ad_visits', [
            'ad_id' => $ad->id,
        ]);
    }

    public function test_visit_without_external_url_not_recorded()
    {
        $response = $this->postJson("/api/public/ads/{$this->ad->id}/track/visit");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ad_visits', [
            'ad_id' => $this->ad->id,
        ]);
    }
}
