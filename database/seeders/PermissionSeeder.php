<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

class PermissionSeeder extends Seeder
{
    private $permissionGroups = [
        'roles' => ['list', 'show', 'create', 'edit', 'delete', 'get-all-permissions'],
        'training_categories' => ['list', 'show', 'create', 'edit', 'delete'],
        'health_warnings' => ['list', 'show', 'create', 'edit', 'delete'],
        'properties' => ['list', 'show', 'create', 'edit', 'delete', 'approve', 'reject'],
    ];

    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        foreach ($this->permissionGroups as $group => $permissions) {
            foreach ($permissions as $permission) {
                $permission = SpatiePermission::firstOrCreate([
                    'name' => "{$group}.{$permission}",
                    'guard_name' => 'web'
                ]);
            }
        }

        // Create roles and assign permissions
        $this->createRolesWithPermissions();
    }

    protected function createRolesWithPermissions()
    {
        // Super Admin - gets all permissions for this guard
        $superAdmin = SpatieRole::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web'
        ]);

        // Assign only permissions that belong to this guard
        $superAdmin->givePermissionTo(
            SpatiePermission::all()
        );
    }
}
