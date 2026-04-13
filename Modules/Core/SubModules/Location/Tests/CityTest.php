<?php

namespace Modules\Core\SubModules\Location\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'cities.list',
            'cities.show',
            'cities.create',
            'cities.edit',
            'cities.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_can_list_cities()
    {
        $country = Country::factory()->create();
        City::factory()->count(3)->create(['country_id' => $country->id]);

        $response = $this->actingAs($this->user)->getJson('/api/location/cities');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_city()
    {
        $country = Country::factory()->create();
        $payload = [
            'name' => 'Test City',
            'country_id' => $country->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/location/cities', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cities', ['name' => 'Test City']);
    }
}
