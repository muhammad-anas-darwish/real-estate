<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Enums\AdMediaType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdTest extends TestCase
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
            'ads.list',
            'ads.show',
            'ads.create',
            'ads.edit',
            'ads.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole($role);

        $this->group = AdGroup::factory()->create();
    }

    public function test_can_list_ads()
    {
        Ad::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/ads');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_ad()
    {
        $payload = [
            'title' => 'Test Ad',
            'description' => 'A test ad description.',
            'ad_group_id' => $this->group->id,
            'media_type' => AdMediaType::IMAGE->value,
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/ads', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ads', ['title' => 'Test Ad']);
    }

    public function test_can_show_own_ad()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/ads/{$ad->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $ad->id);
    }

    public function test_cannot_show_others_ad()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->otherUser->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/ads/{$ad->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_own_ad()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/ads/{$ad->id}", [
                'title' => 'Updated Ad Title',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ads', ['title' => 'Updated Ad Title']);
    }

    public function test_non_owner_cannot_update_ad()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->otherUser->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/ads/{$ad->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_archive_own_ad()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/ads/{$ad->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($ad);
    }

    public function test_non_owner_cannot_archive_ad()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->otherUser->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/ads/{$ad->id}");

        $response->assertStatus(403);
    }

    public function test_can_restore_own_ad()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
        ]);
        $ad->delete();

        $response = $this->actingAs($this->user)
            ->postJson("/api/ads/{$ad->id}/restore");

        $response->assertStatus(200);
        $this->assertNotSoftDeleted($ad);
    }

    public function test_can_set_ad_status()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/ads/{$ad->id}/status", [
                'status' => 'active',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ads', ['id' => $ad->id, 'status' => 'active']);
    }

    public function test_cannot_set_invalid_ad_status()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/ads/{$ad->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_link_property()
    {
        $country = \Modules\Core\SubModules\Location\Entities\Country::factory()->create();
        $city = \Modules\Core\SubModules\Location\Entities\City::factory()->create(['country_id' => $country->id]);
        $property = \Modules\RealEstate\Entities\Property::factory()->create([
            'publisher_id' => $this->user->id,
            'city_id' => $city->id,
        ]);
        $ad = Ad::factory()->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/ads/{$ad->id}/link-property", [
                'property_id' => $property->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ads', ['id' => $ad->id, 'property_id' => $property->id]);
    }

    public function test_can_unlink_property()
    {
        $country = \Modules\Core\SubModules\Location\Entities\Country::factory()->create();
        $city = \Modules\Core\SubModules\Location\Entities\City::factory()->create(['country_id' => $country->id]);
        $property = \Modules\RealEstate\Entities\Property::factory()->create([
            'publisher_id' => $this->user->id,
            'city_id' => $city->id,
        ]);
        $ad = Ad::factory()->create([
            'created_by' => $this->user->id,
            'ad_group_id' => $this->group->id,
            'property_id' => $property->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/ads/{$ad->id}/property");

        $response->assertStatus(200);
        $this->assertDatabaseHas('ads', ['id' => $ad->id, 'property_id' => null]);
    }

    public function test_create_ad_fails_with_missing_title()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/ads', [
                'media_type' => AdMediaType::IMAGE->value,
            ]);

        $response->assertStatus(422);
    }

    public function test_create_ad_fails_with_invalid_media_type()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/ads', [
                'title' => 'Test Ad',
                'media_type' => 'invalid_type',
            ]);

        $response->assertStatus(422);
    }

    public function test_non_owner_cannot_set_status()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->otherUser->id,
            'ad_group_id' => $this->group->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/ads/{$ad->id}/status", [
                'status' => 'active',
            ]);

        $response->assertStatus(403);
    }

    public function test_non_owner_cannot_restore_ad()
    {
        $ad = Ad::factory()->create([
            'created_by' => $this->otherUser->id,
            'ad_group_id' => $this->group->id,
        ]);
        $ad->delete();

        $response = $this->actingAs($this->user)
            ->postJson("/api/ads/{$ad->id}/restore");

        $response->assertStatus(403);
    }
}
