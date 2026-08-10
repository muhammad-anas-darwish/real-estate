<?php

namespace Modules\Crm\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Entities\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'trader'])
            ->get('/api/test/crm-foundation', function () {
                return response()->json(['ok' => true]);
            });

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\PermissionSeeder']);
    }

    public function test_trader_user_can_access_trader_protected_endpoint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('trader');

        $response = $this->actingAs($user)->getJson('/api/test/crm-foundation');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_super_admin_user_can_access_trader_protected_endpoint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->getJson('/api/test/crm-foundation');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_authenticated_user_without_trader_role_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/test/crm-foundation');

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    public function test_guest_user_gets_401(): void
    {
        $response = $this->getJson('/api/test/crm-foundation');

        $response->assertStatus(401);
    }

    public function test_seeder_creates_all_expected_crm_permissions(): void
    {
        $expected = [
            'leads.list', 'leads.show', 'leads.create', 'leads.edit', 'leads.delete',
            'leads.change-status', 'leads.archive', 'leads.restore', 'leads.export',
            'lead_notes.list', 'lead_notes.show', 'lead_notes.create', 'lead_notes.edit', 'lead_notes.delete',
            'lead_follow_ups.list', 'lead_follow_ups.show', 'lead_follow_ups.create',
            'lead_follow_ups.edit', 'lead_follow_ups.delete', 'lead_follow_ups.complete',
            'crm_dashboard.view',
        ];

        foreach ($expected as $perm) {
            $this->assertTrue(
                Permission::where('name', $perm)->exists(),
                "Permission {$perm} should be created by the seeder"
            );
        }
    }

    public function test_trader_role_is_associated_with_all_crm_permissions(): void
    {
        $trader = Role::where('name', 'trader')->first();
        $this->assertNotNull($trader, 'trader role should be created by the seeder');

        $crmPermissions = [
            'leads.list', 'leads.show', 'leads.create', 'leads.edit',
            'leads.change-status', 'leads.archive', 'leads.restore', 'leads.export',
            'lead_notes.list', 'lead_notes.show', 'lead_notes.create', 'lead_notes.edit', 'lead_notes.delete',
            'lead_follow_ups.list', 'lead_follow_ups.show', 'lead_follow_ups.create',
            'lead_follow_ups.edit', 'lead_follow_ups.delete', 'lead_follow_ups.complete',
            'crm_dashboard.view',
        ];

        foreach ($crmPermissions as $perm) {
            $this->assertTrue(
                $trader->hasPermissionTo($perm),
                "trader role should have permission {$perm}"
            );
        }
    }

    public function test_trader_role_is_not_associated_with_other_module_permissions(): void
    {
        $trader = Role::where('name', 'trader')->first();
        $this->assertFalse(
            $trader->hasPermissionTo('properties.create'),
            'trader role should not have properties.create permission'
        );
        $this->assertFalse(
            $trader->hasPermissionTo('users.delete'),
            'trader role should not have users.delete permission'
        );
        $this->assertFalse(
            $trader->hasPermissionTo('payroll.run'),
            'trader role should not have payroll.run permission'
        );
    }
}
