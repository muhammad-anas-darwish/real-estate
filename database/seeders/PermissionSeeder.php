<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

class PermissionSeeder extends Seeder
{
    private $permissionGroups = [
        'roles' => ['list', 'show', 'create', 'edit', 'delete', 'get-all-permissions'],
        'users' => ['list', 'show', 'create', 'edit', 'delete', 'toggle-status'],
        'training_categories' => ['list', 'show', 'create', 'edit', 'delete'],
        'health_warnings' => ['list', 'show', 'create', 'edit', 'delete'],
        'properties' => ['list', 'show', 'create', 'edit', 'delete'],
        'ad_groups' => ['list', 'show', 'create', 'edit', 'delete', 'archive', 'restore', 'set-default'],
        'ads' => ['list', 'show', 'create', 'edit', 'delete', 'archive', 'restore', 'set-status', 'link-property', 'view-analytics', 'export'],
        'countries' => ['list', 'show', 'create', 'edit', 'delete'],
        'cities' => ['list', 'show', 'create', 'edit', 'delete'],
        'expert_requests' => ['list', 'show', 'create', 'cancel'],
        'expert_relationships' => ['list', 'show', 'cancel', 'complete'],
    ];

    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for both web and sanctum guards
        foreach ($this->permissionGroups as $group => $permissions) {
            foreach ($permissions as $permission) {
                foreach (['web', 'sanctum'] as $guardName) {
                    $permission = SpatiePermission::firstOrCreate([
                        'name' => "{$group}.{$permission}",
                        'guard_name' => $guardName,
                    ]);
                }
            }
        }

        // Create roles and assign permissions
        $this->createRolesWithPermissions();
    }

    protected function createRolesWithPermissions()
    {
        // Super Admin - gets all permissions for both guards
        foreach (['web', 'sanctum'] as $guardName) {
            $superAdmin = SpatieRole::firstOrCreate([
                'name' => 'super-admin',
                'guard_name' => $guardName,
            ]);

            // Assign only permissions that belong to this guard
            $superAdmin->givePermissionTo(
                SpatiePermission::where('guard_name', $guardName)->get()
            );
        }
    }
}
