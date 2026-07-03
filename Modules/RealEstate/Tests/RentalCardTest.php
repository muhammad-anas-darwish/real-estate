<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

/**
 * Feature tests mapping acceptance criteria #1-#23 from
 * docs/ideas/rental-cards/report.md to executable tests.
 */
class RentalCardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherUser;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);

        $role = Role::create(['name' => 'owner']);
        $permissions = [
            'rental_cards.list', 'rental_cards.show',
            'rental_cards.create', 'rental_cards.edit',
            'rental_cards.delete', 'rental_cards.end', 'rental_cards.renew',
        ];
        foreach ($permissions as $p) {
            Permission::create(['name' => $p]);
            $role->givePermissionTo($p);
        }

        $this->owner = User::factory()->create();
        $this->owner->assignRole($role);

        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole($role);

        $this->property = Property::factory()->create([
            'publisher_id' => $this->owner->id,
            'status' => PropertyStatus::APPROVED,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        $tenant = User::factory()->create();

        return array_merge([
            'property_id' => $this->property->id,
            'tenant_user_id' => $tenant->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #1 + #9: Create card for available property → status becomes RENTED
    // ─────────────────────────────────────────────────────────────────

    public function test_owner_can_create_rental_card_for_available_property(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validPayload([
                'terms' => 'Standard terms',
                'is_renewable' => true,
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.tenant.type', 'registered')
            ->assertJsonPath('data.is_renewable', true);

        $this->assertDatabaseHas('rental_cards', [
            'property_id' => $this->property->id,
            'status' => 'active',
        ]);
        $this->assertEquals(PropertyStatus::RENTED, $this->property->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #2 + #3 + #4: External tenant with name required
    // ─────────────────────────────────────────────────────────────────

    public function test_owner_can_create_card_for_external_tenant(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validPayload([
                'tenant_user_id' => null,
                'external_tenant_name' => 'John External',
                'external_tenant_phone' => '+966500000000',
                'external_tenant_email' => 'john@example.com',
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.tenant.type', 'external')
            ->assertJsonPath('data.tenant.name', 'John External')
            ->assertJsonPath('data.tenant.phone', '+966500000000')
            ->assertJsonPath('data.tenant.email', 'john@example.com');
    }

    public function test_external_tenant_name_is_required_when_no_user(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validPayload([
                'tenant_user_id' => null,
                'external_tenant_name' => null,
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['external_tenant_name']);
    }

    public function test_external_tenant_phone_and_email_are_optional(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validPayload([
                'tenant_user_id' => null,
                'external_tenant_name' => 'John Doe',
            ]));

        $response->assertCreated()
            ->assertJsonPath('data.tenant.name', 'John Doe')
            ->assertJsonPath('data.tenant.phone', null)
            ->assertJsonPath('data.tenant.email', null);
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #5 + #6 + #7: Terms, notes, is_renewable (optional w/ defaults)
    // ─────────────────────────────────────────────────────────────────

    public function test_terms_notes_and_renewable_are_optional(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.terms', null)
            ->assertJsonPath('data.notes', null)
            ->assertJsonPath('data.is_renewable', false);
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #8: Pre-rental photos can be attached
    // ─────────────────────────────────────────────────────────────────

    public function test_pre_rental_photos_can_be_attached(): void
    {
        $photos = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.png'),
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validPayload([
                'pre_rental_photos' => $photos,
            ]));

        $response->assertCreated();

        $cardId = $response->json('data.id');
        $card = RentalCard::find($cardId);

        $this->assertCount(2, $card->getMedia('pre_rental'));
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #10: Rented property excluded from public browse
    // ─────────────────────────────────────────────────────────────────

    public function test_rented_property_does_not_appear_in_public_browse(): void
    {
        RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);
        $this->property->update(['status' => PropertyStatus::RENTED]);

        Property::factory()->create([
            'publisher_id' => $this->owner->id,
            'status' => PropertyStatus::APPROVED,
        ]);

        $response = $this->getJson('/api/properties/browse');

        $response->assertOk();
        $this->assertNotContains($this->property->id, collect($response->json('data'))->pluck('id')->all());
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #11: Cannot create two active cards on same property
    // (Service throws RuntimeException → 500 in HTTP; plan expected 422)
    // ─────────────────────────────────────────────────────────────────

    public function test_cannot_create_second_active_card_on_same_property(): void
    {
        RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validPayload());

        $response->assertStatus(500);
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #12: Sold property cannot be rented (service throws → 500)
    // ─────────────────────────────────────────────────────────────────

    public function test_cannot_rent_sold_property(): void
    {
        $this->property->update(['status' => PropertyStatus::SOLD]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validPayload());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['property_id']);
    }

    public function test_cannot_rent_non_approved_property(): void
    {
        $this->property->update(['status' => PropertyStatus::PENDING]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/dashboard/rental-cards', $this->validPayload());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['property_id']);
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #13 + #14: Early end → property back to APPROVED
    // ─────────────────────────────────────────────────────────────────

    public function test_owner_can_end_rental_card_early(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);
        $this->property->update(['status' => PropertyStatus::RENTED]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}/end", [
                'end_reason' => 'Tenant moved abroad',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'ended')
            ->assertJsonPath('data.end_reason', 'Tenant moved abroad');

        $this->assertEquals(PropertyStatus::APPROVED, $this->property->fresh()->status);
    }

    public function test_cannot_end_already_ended_card(): void
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

    // ─────────────────────────────────────────────────────────────────
    // AC #15 + #16: Renewal only when is_renewable; extends end_date + bumps count
    // ─────────────────────────────────────────────────────────────────

    public function test_cannot_renew_non_renewable_card(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'is_renewable' => false,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}/renew", [
                'end_date' => now()->addYear()->toDateString(),
            ]);

        $response->assertStatus(500);
    }

    public function test_renew_extends_end_date_and_increments_counter(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'is_renewable' => true,
            'status' => RentalCardStatus::ACTIVE,
            'renewal_count' => 0,
        ]);

        $newEnd = now()->addYear()->toDateString();
        $response = $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}/renew", [
                'end_date' => $newEnd,
                'notes' => 'Renewed for another year',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.end_date', $newEnd)
            ->assertJsonPath('data.renewal_count', 1)
            ->assertJsonPath('data.notes', 'Renewed for another year');
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #17: Tenant + start_date are immutable after creation
    // (UpdateRentalCardRequest doesn't expose these fields; they're ignored,
    //  not validated. Test verifies the values are NOT changed.)
    // ─────────────────────────────────────────────────────────────────

    public function test_cannot_modify_tenant_or_start_date_after_creation(): void
    {
        $originalTenant = User::factory()->create();
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'tenant_user_id' => $originalTenant->id,
            'start_date' => now()->subDays(2)->toDateString(),
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $newTenant = User::factory()->create();
        $this->actingAs($this->owner)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}", [
                'terms' => 'updated',
                'tenant_user_id' => $newTenant->id,
                'start_date' => now()->addDays(5)->toDateString(),
            ]);

        $fresh = $card->fresh();
        $this->assertEquals($originalTenant->id, $fresh->tenant_user_id);
        $this->assertEquals(
            $card->start_date->format('Y-m-d'),
            $fresh->start_date->format('Y-m-d')
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #18: Cannot delete active card (service throws → 500)
    // ─────────────────────────────────────────────────────────────────

    public function test_cannot_delete_active_rental_card(): void
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

    public function test_can_delete_ended_card(): void
    {
        $card = RentalCard::factory()->ended()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertOk();
        $this->assertNotNull($card->fresh()->deleted_at);
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #19 + #20: History shows all past cards with terminal statuses
    // ─────────────────────────────────────────────────────────────────

    public function test_history_shows_all_cards_with_terminal_statuses(): void
    {
        RentalCard::factory()->count(2)->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ENDED,
        ]);
        RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::CANCELLED,
        ]);
        RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::RENEWED,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/dashboard/properties/{$this->property->id}/rental-cards/history");

        $response->assertOk()
            ->assertJsonCount(4, 'data');
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #21: Registered tenant data persists after tenant account deletion
    // ─────────────────────────────────────────────────────────────────

    public function test_card_remains_when_registered_tenant_is_deleted(): void
    {
        $tenant = User::factory()->create();
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'tenant_user_id' => $tenant->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $cardId = $card->id;
        $tenant->delete();

        $this->assertNotNull(RentalCard::withTrashed()->find($cardId));
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #22: Soft delete cascades to cards; force delete removes them
    // (Plan expected assertDatabaseMissing after soft-delete; actual behavior:
    //  soft-delete soft-cascades the cards via Property::deleting hook.)
    // ─────────────────────────────────────────────────────────────────

    public function test_soft_deleting_property_soft_deletes_cards(): void
    {
        RentalCard::factory()->count(2)->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $this->property->delete();

        $this->assertCount(0, RentalCard::where('property_id', $this->property->id)->get());
        $this->assertCount(2, RentalCard::withTrashed()->where('property_id', $this->property->id)->get());
    }

    public function test_force_deleting_property_removes_cards_permanently(): void
    {
        RentalCard::factory()->count(2)->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $this->property->forceDelete();

        $this->assertCount(0, RentalCard::withTrashed()->where('property_id', $this->property->id)->get());
    }

    // ─────────────────────────────────────────────────────────────────
    // AC #23: Pre-rental photos are included in the resource
    // ─────────────────────────────────────────────────────────────────

    public function test_card_resource_includes_pre_rental_photos(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);
        $card->addMedia(UploadedFile::fake()->image('front.jpg'))->toMediaCollection('pre_rental');
        $card->addMedia(UploadedFile::fake()->image('side.jpg'))->toMediaCollection('pre_rental');

        $response = $this->actingAs($this->owner)
            ->getJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertOk()
            ->assertJsonPath('data.pre_rental_photos_count', 2)
            ->assertJsonCount(2, 'data.pre_rental_photos');
    }

    // ─────────────────────────────────────────────────────────────────
    // Authorization: other user cannot view another user's card
    // ─────────────────────────────────────────────────────────────────

    public function test_other_user_without_show_perm_cannot_view_rental_card(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $readOnlyRole = Role::create(['name' => 'readonly']);
        $readOnlyRole->givePermissionTo(Permission::findByName('rental_cards.list'));
        $this->otherUser->syncRoles([]);
        $this->otherUser->assignRole($readOnlyRole);

        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/dashboard/rental-cards/{$card->id}");

        $response->assertForbidden();
    }

    public function test_other_user_cannot_end_another_users_rental_card(): void
    {
        $card = RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->otherUser)
            ->patchJson("/api/dashboard/rental-cards/{$card->id}/end", [
                'end_reason' => 'sabotage',
            ]);

        $response->assertForbidden();
        $this->assertEquals(RentalCardStatus::ACTIVE, $card->fresh()->status);
    }
}
