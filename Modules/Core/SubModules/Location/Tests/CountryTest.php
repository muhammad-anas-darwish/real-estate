<?php

namespace Modules\Core\SubModules\Location\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\Country;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CountryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'countries.list',
            'countries.show',
            'countries.create',
            'countries.edit',
            'countries.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_can_list_countries()
    {
        Country::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/location/countries');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_country()
    {
        $payload = [
            'name' => 'Test Country',
            'code' => 'TC',
            'phone_code' => '+123',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/location/countries', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('countries', ['name' => 'Test Country']);
    }
}
