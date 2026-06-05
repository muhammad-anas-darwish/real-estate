<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $country;

    protected $city;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'properties.list',
            'properties.show',
            'properties.create',
            'properties.edit',
            'properties.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->country = Country::factory()->create();
        $this->city = City::factory()->create(['country_id' => $this->country->id]);
    }

    public function test_can_list_properties()
    {
        Property::factory()->count(3)->create(['publisher_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/properties');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_property()
    {
        $payload = [
            'name' => 'Test Property',
            'description' => 'A beautiful property description.',
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'property_type' => PropertyType::Villa->value,
            'type_of_contract' => TypeOfContract::Sale->value,
            'rooms' => 3,
            'bathrooms' => 2,
            'area' => 150.5,
            'price' => 250000.00,
            'currency' => 'USD',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/properties', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('properties', ['name' => 'Test Property']);
    }
}
