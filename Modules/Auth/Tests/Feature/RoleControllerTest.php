<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'roles.list', 'roles.show', 'roles.create', 'roles.edit', 'roles.delete',
        ];
        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm]);
        }

        $adminRole = Role::create(['name' => 'super-admin']);
        $adminRole->givePermissionTo($permissions);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->unauthorizedUser = User::factory()->create();
    }

    public function test_can_list_roles()
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'trader']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/roles');

        $response->assertStatus(200);
    }

    public function test_list_roles_requires_auth()
    {
        $response = $this->getJson('/api/roles');
        $response->assertStatus(401);
    }

    public function test_list_roles_requires_permission()
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->getJson('/api/roles');

        $response->assertStatus(403);
    }

    public function test_can_show_role()
    {
        $role = Role::create(['name' => 'trader']);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/roles/{$role->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'trader');
    }

    public function test_show_nonexistent_role_returns_404()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/roles/99999');

        $response->assertStatus(404);
    }

    public function test_can_create_role()
    {
        Permission::create(['name' => 'properties.list']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/roles', [
                'name' => 'property-manager',
                'permissions' => ['properties.list'],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('roles', ['name' => 'property-manager']);
    }

    public function test_create_role_validates_unique_name()
    {
        Role::create(['name' => 'trader']);
        Permission::create(['name' => 'properties.list']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/roles', [
                'name' => 'trader',
                'permissions' => ['properties.list'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_create_role_validates_required_name()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/roles', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_role()
    {
        $role = Role::create(['name' => 'old-name']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/roles/{$role->id}", [
                'name' => 'new-name',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'new-name']);
    }

    public function test_can_delete_role()
    {
        $role = Role::create(['name' => 'temporary']);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/roles/{$role->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_can_assign_permissions_to_role()
    {
        $role = Role::create(['name' => 'editor']);
        $perm1 = Permission::create(['name' => 'properties.list']);
        $perm2 = Permission::create(['name' => 'properties.show']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/roles/{$role->id}/permissions", [
                'permissions' => ['properties.list', 'properties.show'],
            ]);

        $response->assertStatus(200);
        $this->assertTrue($role->fresh()->hasPermissionTo('properties.list'));
        $this->assertTrue($role->fresh()->hasPermissionTo('properties.show'));
    }

    public function test_assign_permissions_validates_existing_permissions()
    {
        $role = Role::create(['name' => 'editor']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/roles/{$role->id}/permissions", [
                'permissions' => ['nonexistent.perm'],
            ]);

        $response->assertStatus(422);
    }

    public function test_can_list_permissions()
    {
        Permission::create(['name' => 'properties.list']);
        Permission::create(['name' => 'users.list']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/permissions');

        $response->assertStatus(200);
    }

    public function test_permissions_requires_auth()
    {
        $response = $this->getJson('/api/permissions');
        $response->assertStatus(401);
    }
}
