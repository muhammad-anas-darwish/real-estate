<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\DTOs\CreateRentalCardDTO;
use Modules\RealEstate\DTOs\EndRentalCardDTO;
use Modules\RealEstate\DTOs\RenewRentalCardDTO;
use Modules\RealEstate\DTOs\UpdateRentalCardDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\RentalCardStatus;
use Modules\RealEstate\Services\RentalCardService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RentalCardServiceTest extends TestCase
{
    use RefreshDatabase;

    private RentalCardService $service;

    private User $owner;

    private User $tenant;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RentalCardService::class);

        Role::create(['name' => 'super-admin']);

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);

        $this->owner = User::factory()->create();
        $this->tenant = User::factory()->create();

        $this->property = Property::factory()->create([
            'publisher_id' => $this->owner->id,
            'status' => PropertyStatus::APPROVED,
        ]);
    }

    private function makeCreateDto(array $overrides = []): CreateRentalCardDTO
    {
        return CreateRentalCardDTO::fromRequest(array_merge([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'tenant_user_id' => $this->tenant->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ], $overrides));
    }

    public function test_creates_a_card_and_marks_property_as_rented(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());

        $this->assertNotNull($card->id);
        $this->assertEquals(RentalCardStatus::ACTIVE, $card->status);
        $this->assertEquals(PropertyStatus::RENTED, $this->property->fresh()->status);
    }

    public function test_rejects_creation_when_property_is_sold(): void
    {
        $this->property->update(['status' => PropertyStatus::SOLD]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/sold|cannot be rented/i');

        $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());
    }

    public function test_rejects_creation_when_property_not_approved(): void
    {
        $this->property->update(['status' => PropertyStatus::PENDING]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not available/i');

        $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());
    }

    public function test_rejects_creation_when_caller_is_not_owner(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not authorized|owner/i');

        $dto = CreateRentalCardDTO::fromRequest([
            'property_id' => $this->property->id,
            'owner_id' => 999999,
            'tenant_user_id' => $this->tenant->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ]);

        $this->actingAs($this->owner)
            ->service->create($dto);
    }

    public function test_rejects_creation_when_active_card_exists(): void
    {
        RentalCard::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $this->property->update(['status' => PropertyStatus::APPROVED]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/active rental card/i');

        $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());
    }

    public function test_rejects_creation_when_end_date_before_start_date(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/end date/i');

        $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto([
                'start_date' => now()->addMonths(6)->toDateString(),
                'end_date' => now()->toDateString(),
            ]));
    }

    public function test_external_tenant_requires_name(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/external tenant name/i');

        $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto([
                'tenant_user_id' => null,
                'external_tenant_name' => null,
            ]));
    }

    public function test_ends_card_and_restores_property_to_approved(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());

        $ended = $this->actingAs($this->owner)
            ->service->end($card->id, EndRentalCardDTO::fromRequest(['end_reason' => 'tenant moved']));

        $this->assertEquals(RentalCardStatus::ENDED, $ended->status);
        $this->assertNotNull($ended->ended_at);
        $this->assertEquals($this->owner->id, $ended->ended_by);
        $this->assertEquals(PropertyStatus::APPROVED, $this->property->fresh()->status);
    }

    public function test_rejects_ending_non_active_card(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());

        $this->actingAs($this->owner)
            ->service->end($card->id, EndRentalCardDTO::fromRequest(['end_reason' => 'first end']));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not active/i');

        $this->actingAs($this->owner)
            ->service->end($card->id, EndRentalCardDTO::fromRequest(['end_reason' => 'second end']));
    }

    public function test_renews_card_when_is_renewable(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto(['is_renewable' => true]));

        $newEndDate = now()->addYear()->toDateString();

        $renewed = $this->actingAs($this->owner)
            ->service->renew($card->id, RenewRentalCardDTO::fromRequest([
                'end_date' => $newEndDate,
                'terms' => 'renewed terms',
            ]));

        $this->assertEquals(RentalCardStatus::ACTIVE, $renewed->status);
        $this->assertEquals(1, $renewed->renewal_count);
        $this->assertNotNull($renewed->renewed_at);
        $this->assertEquals('renewed terms', $renewed->terms);
    }

    public function test_rejects_renewal_when_not_renewable(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto(['is_renewable' => false]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/renewable/i');

        $this->actingAs($this->owner)
            ->service->renew($card->id, RenewRentalCardDTO::fromRequest([
                'end_date' => now()->addYear()->toDateString(),
            ]));
    }

    public function test_update_does_not_change_immutable_fields(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());

        $originalPropertyId = $card->property_id;
        $originalOwnerId = $card->owner_id;
        $originalTenantId = $card->tenant_user_id;
        $originalStartDate = $card->start_date->format('Y-m-d');

        $this->actingAs($this->owner)
            ->service->update($card->id, UpdateRentalCardDTO::fromRequest([
                'terms' => 'new terms',
                'notes' => 'new notes',
            ]));

        $fresh = $card->fresh();
        $this->assertEquals($originalPropertyId, $fresh->property_id);
        $this->assertEquals($originalOwnerId, $fresh->owner_id);
        $this->assertEquals($originalTenantId, $fresh->tenant_user_id);
        $this->assertEquals($originalStartDate, $fresh->start_date->format('Y-m-d'));
        $this->assertEquals('new terms', $fresh->terms);
        $this->assertEquals('new notes', $fresh->notes);
    }

    public function test_cannot_delete_active_card(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/active rental card cannot be deleted/i');

        $this->actingAs($this->owner)->service->delete($card->id);
    }

    public function test_can_delete_ended_card(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());

        $this->actingAs($this->owner)
            ->service->end($card->id, EndRentalCardDTO::fromRequest(['end_reason' => 'done']));

        $this->actingAs($this->owner)->service->delete($card->id);

        $this->assertNotNull($card->fresh()->deleted_at);
    }

    public function test_soft_deleting_property_soft_deletes_rental_cards(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());

        $this->property->delete();

        $this->assertNotNull($card->fresh()->deleted_at);
    }

    public function test_force_deleting_property_removes_rental_cards(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());

        $this->property->forceDelete();

        $this->assertNull(RentalCard::withTrashed()->find($card->id));
    }

    public function test_super_admin_can_manage_others_cards(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto());

        $ended = $this->actingAs($admin)
            ->service->end($card->id, EndRentalCardDTO::fromRequest(['end_reason' => 'admin action']));

        $this->assertEquals(RentalCardStatus::ENDED, $ended->status);
    }
}
