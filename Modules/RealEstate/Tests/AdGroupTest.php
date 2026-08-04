<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdGroupTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'ad_groups.list',
            'ad_groups.show',
            'ad_groups.create',
            'ad_groups.edit',
            'ad_groups.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_can_list_ad_groups()
    {
        AdGroup::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/ad-groups');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_ad_group()
    {
        $payload = [
            'name' => 'Test Ad Group',
            'description' => 'A test ad group description.',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/ad-groups', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ad_groups', ['name' => 'Test Ad Group']);
    }

    public function test_can_show_ad_group()
    {
        $group = AdGroup::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/dashboard/ad-groups/{$group->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $group->id);
    }

    public function test_can_update_ad_group()
    {
        $group = AdGroup::factory()->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/dashboard/ad-groups/{$group->id}", [
                'name' => 'Updated Group Name',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ad_groups', ['name' => 'Updated Group Name']);
    }

    public function test_can_archive_ad_group()
    {
        $group = AdGroup::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/dashboard/ad-groups/{$group->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($group);
    }

    public function test_can_restore_ad_group()
    {
        $group = AdGroup::factory()->create();
        $group->delete();

        $response = $this->actingAs($this->user)
            ->postJson("/api/dashboard/ad-groups/{$group->id}/restore");

        $response->assertStatus(200);
        $this->assertNotSoftDeleted($group);
    }

    public function test_create_ad_group_fails_with_duplicate_name()
    {
        AdGroup::factory()->create(['name' => 'Duplicate Name']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/dashboard/ad-groups', [
                'name' => 'Duplicate Name',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_ad_group_requires_name()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/dashboard/ad-groups', [
                'description' => 'Missing name',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_set_default_ad()
    {
        $group = AdGroup::factory()->create();
        $ad = Ad::factory()->create(['ad_group_id' => $group->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/dashboard/ad-groups/{$group->id}/set-default", [
                'ad_id' => $ad->id,
                'ad_group_id' => $group->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ads', [
            'id' => $ad->id,
            'is_default' => true,
        ]);
    }

    public function test_can_remove_default_ad()
    {
        $group = AdGroup::factory()->create();
        Ad::factory()->create([
            'ad_group_id' => $group->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/dashboard/ad-groups/{$group->id}/default");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ads', [
            'ad_group_id' => $group->id,
            'is_default' => true,
        ]);
    }
}
