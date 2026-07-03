<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\RentalCardStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RentalCardApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private User $tenant;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);

        $role = Role::create(['name' => 'publisher']);
        $perms = [
            'rental_cards.list', 'rental_cards.show',
            'rental_cards.create', 'rental_cards.edit',
            'rental_cards.delete', 'rental_cards.end', 'rental_cards.renew',
        ];
        foreach ($perms as $p) {
            Permission::create(['name' => $p]);
            $role->givePermissionTo($p);
        }

        $this->owner = User::factory()->create();
        $this->owner->assignRole($role);

        $this->other = User::factory()->create();
        $this->other->assignRole($role);

        $this->tenant = User::factory()->create();

        $this->property = Property::factory()->create([
            'publisher_id' => $this->owner->id,
            'status' => PropertyStatus::APPROVED,
        ]);
    }

    private function validCreatePayload(array $overrides = []): array
    {
        return array_merge([
            'property_id' => $this->property->id,
            'tenant_user_id' => $this->tenant->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'terms' => 'rent terms',
        ], $overrides);
    }

    public function test_store_creates_card_and_marks_property_rented(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validCreatePayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.property_id', $this->property->id)
            ->assertJsonPath('data.status', 'active');

        $this->assertEquals(PropertyStatus::RENTED, $this->property->fresh()->status);
        $this->assertDatabaseHas('rental_cards', [
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => 'active',
        ]);
    }

    public function test_store_with_external_tenant_requires_name(): void
    {
        $payload = $this->validCreatePayload(['tenant_user_id' => null]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['external_tenant_name']);
    }

    public function test_store_rejects_property_not_owned_by_caller(): void
    {
        $response = $this->actingAs($this->other)
            ->postJson('/api/dashboard/rental-cards', $this->validCreatePayload());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['property_id']);
    }

    public function test_store_rejects_property_not_approved(): void
    {
        $this->property->update(['status' => PropertyStatus::PENDING]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validCreatePayload());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['property_id']);
    }

    public function test_store_rejects_end_date_before_start_date(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validCreatePayload([
                'start_date' => now()->addMonths(6)->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_index_lists_only_owned_cards_by_default(): void
    {
        RentalCard::factory()->count(2)->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);
        RentalCard::factory()->create([
            'property_id' => Property::factory()->create(['publisher_id' => $this->other->id, 'status' => PropertyStatus::APPROVED])->id,
            'owner_id' => $this->other->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/dashboard/rental-cards');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_returns_card_for_owner(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $card->id);
    }

    public function test_show_forbidden_for_non_owner_without_show_perm(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $roleWithoutShow = Role::create(['name' => 'readonly']);
        $roleWithoutShow->givePermissionTo(Permission::findByName('rental_cards.list'));
        $this->other->syncRoles([]);
        $this->other->assignRole($roleWithoutShow);

        $response = $this->actingAs($this->other)
            ->getJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertStatus(403);
    }

    public function test_active_returns_active_card(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/dashboard/properties/{$this->property->id}/rental-cards/active");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $card->id);
    }

    public function test_active_returns_null_when_no_active_card(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/dashboard/properties/{$this->property->id}/rental-cards/active");

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function test_history_returns_all_cards_for_property(): void
    {
        RentalCard::factory()->count(3)->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/dashboard/properties/{$this->property->id}/rental-cards/history");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_update_modifies_allowed_fields_only(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}", [
                'terms' => 'updated terms',
                'notes' => 'updated notes',
                'is_renewable' => true,
            ]);

        $response->assertStatus(200);

        $fresh = $card->fresh();
        $this->assertEquals('updated terms', $fresh->terms);
        $this->assertEquals('updated notes', $fresh->notes);
        $this->assertTrue($fresh->is_renewable);
    }

    public function test_update_rejects_property_id_change(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}", [
                'terms' => 'updated',
                'property_id' => 99999,
            ]);

        $response->assertStatus(200);

        $this->assertEquals($this->property->id, $card->fresh()->property_id);
    }

    public function test_end_ends_active_card(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);
        $this->property->update(['status' => PropertyStatus::RENTED]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}/end", [
                'end_reason' => 'tenant moved',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'ended');

        $this->assertEquals(PropertyStatus::APPROVED, $this->property->fresh()->status);
    }

    public function test_end_rejects_non_active_card(): void
    {
        $card = RentalCard::factory()->ended()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}/end", [
                'end_reason' => 'try again',
            ]);

        $response->assertStatus(500);
    }

    public function test_renew_extends_end_date_when_renewable(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
            'is_renewable' => true,
        ]);

        $newEndDate = now()->addYear()->toDateString();

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}/renew", [
                'end_date' => $newEndDate,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.renewal_count', 1);

        $this->assertEquals($newEndDate, $card->fresh()->end_date->format('Y-m-d'));
    }

    public function test_renew_rejects_non_renewable_card(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
            'is_renewable' => false,
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}/renew", [
                'end_date' => now()->addYear()->toDateString(),
            ]);

        $response->assertStatus(500);
    }

    public function test_destroy_rejects_active_card(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertStatus(500);
    }

    public function test_destroy_allows_non_active_card(): void
    {
        $card = RentalCard::factory()->ended()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertStatus(200);
        $this->assertNotNull($card->fresh()->deleted_at);
    }

    public function test_super_admin_can_view_any_card(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::create(['name' => 'super-admin']);
        $adminRole->givePermissionTo(Permission::all());
        $admin->assignRole($adminRole);

        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertStatus(200);
    }
}
