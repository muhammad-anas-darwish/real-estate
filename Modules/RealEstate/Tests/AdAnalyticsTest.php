<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Entities\AdView;
use Modules\RealEstate\Entities\AdVisit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected AdGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'ads.view-analytics',
            'ads.export',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->otherUser = User::factory()->create();

        $this->group = AdGroup::factory()->create();
    }

    public function test_can_get_dashboard_analytics()
    {
        Ad::factory()->count(2)->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/dashboard');

        $response->assertStatus(200);
        $response->assertJsonPath('data.total_ads', 2);
    }

    public function test_can_get_group_analytics_as_owner()
    {
        $group = AdGroup::factory()->create(['created_by' => $this->user->id]);
        Ad::factory()->count(2)->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $group->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/analytics/groups/{$group->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.group_id', $group->id);
    }

    public function test_non_owner_cannot_get_group_analytics()
    {
        $group = AdGroup::factory()->create(['created_by' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/analytics/groups/{$group->id}");

        $response->assertStatus(403);
    }

    public function test_can_get_ad_analytics()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
        ]);
        AdView::create([
            'ad_id' => $ad->id,
            'viewed_at' => now(),
        ]);
        AdVisit::create([
            'ad_id' => $ad->id,
            'external_url' => 'https://example.com',
            'visited_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/analytics/ads/{$ad->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.ad_id', $ad->id);
        $response->assertJsonPath('data.total_views', 1);
        $response->assertJsonPath('data.total_visits', 1);
    }

    public function test_can_export_report()
    {
        Ad::factory()->count(2)->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/export');

        $response->assertStatus(200);
    }
}
