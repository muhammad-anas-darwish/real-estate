<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\RentalCardStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verifies that rented/sold properties are excluded from public-facing
 * surfaces (browse, ads, sponsored ads) — Acceptance criterion #10 deep
 * coverage and the report's scenario 7.
 */
class RentalCardPublicFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);

        $this->owner = User::factory()->create();
    }

    private function makeProperty(PropertyStatus $status, ?int $publisherId = null): Property
    {
        return Property::factory()->create([
            'publisher_id' => $publisherId ?? $this->owner->id,
            'status' => $status->value,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Public browse endpoint (/api/properties/browse)
    // ─────────────────────────────────────────────────────────────────

    public function test_public_browse_includes_only_approved_and_sold_properties(): void
    {
        $approved = $this->makeProperty(PropertyStatus::APPROVED);
        $sold = $this->makeProperty(PropertyStatus::SOLD);
        $pending = $this->makeProperty(PropertyStatus::PENDING);
        $rejected = $this->makeProperty(PropertyStatus::REJECTED);

        $response = $this->getJson('/api/properties/browse');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($approved->id, $ids);
        $this->assertContains($sold->id, $ids);
        $this->assertNotContains($pending->id, $ids);
        $this->assertNotContains($rejected->id, $ids);
    }

    public function test_rented_property_excluded_from_public_browse(): void
    {
        $rented = $this->makeProperty(PropertyStatus::RENTED);
        $available = $this->makeProperty(PropertyStatus::APPROVED);

        RentalCard::factory()->create([
            'property_id' => $rented->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $response = $this->getJson('/api/properties/browse');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertNotContains($rented->id, $ids);
        $this->assertContains($available->id, $ids);
    }

    public function test_ended_rental_card_restores_property_to_public_browse(): void
    {
        $property = $this->makeProperty(PropertyStatus::RENTED);

        RentalCard::factory()->create([
            'property_id' => $property->id,
            'owner_id' => $this->owner->id,
            'status' => RentalCardStatus::ACTIVE,
        ]);

        $beforeResponse = $this->getJson('/api/properties/browse');
        $this->assertNotContains($property->id, collect($beforeResponse->json('data'))->pluck('id')->all());

        $property->update(['status' => PropertyStatus::APPROVED]);

        $afterResponse = $this->getJson('/api/properties/browse');
        $this->assertContains($property->id, collect($afterResponse->json('data'))->pluck('id')->all());
    }

    // ─────────────────────────────────────────────────────────────────
    // Public show endpoint (/api/properties/{id}/details)
    // ─────────────────────────────────────────────────────────────────

    public function test_public_show_hides_rented_property_detail(): void
    {
        $property = $this->makeProperty(PropertyStatus::RENTED);

        $response = $this->getJson("/api/properties/{$property->id}/details");

        $response->assertNotFound();
    }

    public function test_public_show_returns_sold_property_detail(): void
    {
        $property = $this->makeProperty(PropertyStatus::SOLD);

        $response = $this->getJson("/api/properties/{$property->id}/details");

        $response->assertOk()
            ->assertJsonPath('data.id', $property->id);
    }

    // ─────────────────────────────────────────────────────────────────
    // AdDisplayService: ads tied to rented properties are filtered
    // ─────────────────────────────────────────────────────────────────

    public function test_ad_display_excludes_ads_for_rented_properties(): void
    {
        $role = Role::create(['name' => 'admin']);
        foreach (['ads.list', 'ads.show', 'ads.create', 'ads.edit', 'ads.delete'] as $p) {
            Permission::create(['name' => $p]);
        }
        $this->owner->assignRole($role);

        $rented = $this->makeProperty(PropertyStatus::RENTED);
        $available = $this->makeProperty(PropertyStatus::APPROVED);

        $group = AdGroup::factory()->create();
        Ad::factory()->create([
            'ad_group_id' => $group->id,
            'property_id' => $rented->id,
            'status' => AdStatus::ACTIVE,
            'is_default' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);
        Ad::factory()->create([
            'ad_group_id' => $group->id,
            'property_id' => $available->id,
            'status' => AdStatus::ACTIVE,
            'is_default' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $displayService = app(\Modules\RealEstate\Services\AdDisplayService::class);
        $shown = $displayService->getAdsForDisplay($group->id);

        $this->assertCount(1, $shown);
        $this->assertEquals($available->id, $shown->first()->property_id);
    }

    public function test_ad_display_excludes_ads_for_sold_properties(): void
    {
        $sold = $this->makeProperty(PropertyStatus::SOLD);

        $group = AdGroup::factory()->create();
        Ad::factory()->create([
            'ad_group_id' => $group->id,
            'property_id' => $sold->id,
            'status' => AdStatus::ACTIVE,
            'is_default' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $displayService = app(\Modules\RealEstate\Services\AdDisplayService::class);
        $shown = $displayService->getAdsForDisplay($group->id);

        $this->assertCount(0, $shown);
    }
}
