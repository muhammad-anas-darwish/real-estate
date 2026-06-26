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
        'viewings' => ['list', 'show', 'create', 'edit', 'delete', 'confirm', 'cancel', 'reschedule', 'complete'],
        'ad_groups' => ['list', 'show', 'create', 'edit', 'delete', 'archive', 'restore', 'set-default'],
        'ads' => ['list', 'show', 'create', 'edit', 'delete', 'archive', 'restore', 'set-status', 'link-property', 'view-analytics', 'export'],
        'countries' => ['list', 'show', 'create', 'edit', 'delete'],
        'cities' => ['list', 'show', 'create', 'edit', 'delete'],
        'subscription_plans' => ['list', 'show', 'create', 'edit', 'delete'],
        'subscription_features' => ['list', 'show', 'create', 'edit', 'delete'],
        'subscription_plan_features' => ['list', 'show', 'create', 'edit', 'delete'],
        'subscription_discounts' => ['list', 'show', 'create', 'edit', 'delete'],
        'offices' => ['list', 'show', 'verify', 'unverify', 'list-upgrade-requests', 'approve-upgrade', 'reject-upgrade'],
        'publishers' => ['list', 'show'],
        'reviews' => ['list', 'show', 'delete'],
        'service_providers' => ['list', 'show', 'verify', 'unverify'],
        'service_requests' => ['list', 'show'],
        'accounts' => ['list', 'show', 'create', 'edit', 'delete'],
        'journal_entries' => ['list', 'show', 'create', 'edit', 'delete', 'post'],
        'trial_balance' => ['view'],
        'payroll' => ['list', 'manage', 'run'],
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
                    'guard_name' => 'web',
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
            'guard_name' => 'web',
        ]);

        // Assign only permissions that belong to this guard
        $superAdmin->givePermissionTo(
            SpatiePermission::all()
        );
    }
}
