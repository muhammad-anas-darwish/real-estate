<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyViewing;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;
use Modules\RealEstate\Enums\ViewingStatus;
use Modules\RealEstate\Enums\ViewingType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyViewingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $agent;

    protected Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        $viewingPermissions = [
            'viewings.list',
            'viewings.show',
            'viewings.create',
            'viewings.edit',
            'viewings.delete',
            'viewings.confirm',
            'viewings.cancel',
            'viewings.reschedule',
            'viewings.complete',
            'properties.list',
            'properties.show',
            'properties.create',
            'properties.edit',
            'properties.delete',
        ];

        foreach ($viewingPermissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->agent = User::factory()->create();
        $this->agent->assignRole($role);

        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $this->property = Property::factory()->create([
            'publisher_id' => $this->agent->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'status' => PropertyStatus::APPROVED,
        ]);
    }

    public function test_can_schedule_viewing()
    {
        $scheduledAt = now()->addDay()->setHour(14)->setMinute(0)->setSecond(0);

        $payload = [
            'property_id' => $this->property->id,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'duration_minutes' => 30,
            'buffer_minutes' => 15,
            'viewing_type' => ViewingType::IN_PERSON->value,
            'contact_name' => 'John Doe',
            'contact_phone' => '123456789',
            'notes' => 'Looking forward to seeing the property',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/viewings', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', ViewingStatus::PENDING->value);
        $response->assertJsonPath('data.property_id', $this->property->id);
        $this->assertDatabaseHas('property_viewings', [
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
        ]);
    }

    public function test_cannot_schedule_viewing_with_confling_time()
    {
        $scheduledAt = now()->addDay()->setHour(14)->setMinute(0)->setSecond(0);

        PropertyViewing::factory()->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 30,
            'buffer_minutes' => 15,
            'status' => ViewingStatus::CONFIRMED,
        ]);

        $payload = [
            'property_id' => $this->property->id,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'duration_minutes' => 30,
            'buffer_minutes' => 15,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/viewings', $payload);

        $response->assertStatus(500);
    }

    public function test_cannot_schedule_in_past()
    {
        $payload = [
            'property_id' => $this->property->id,
            'scheduled_at' => now()->subHour()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/viewings', $payload);

        $response->assertStatus(422);
    }

    public function test_cannot_schedule_with_too_little_notice()
    {
        $payload = [
            'property_id' => $this->property->id,
            'scheduled_at' => now()->addMinutes(30)->toDateTimeString(),
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/viewings', $payload);

        $response->assertStatus(500);
    }

    public function test_agent_can_confirm_viewing()
    {
        $viewing = PropertyViewing::factory()->pending()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/viewings/{$viewing->id}/confirm"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::CONFIRMED->value);
        $this->assertDatabaseHas('property_viewings', [
            'id' => $viewing->id,
            'status' => ViewingStatus::CONFIRMED->value,
        ]);
    }

    public function test_agent_can_cancel_viewing()
    {
        $viewing = PropertyViewing::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/viewings/{$viewing->id}/cancel",
            ['status' => ViewingStatus::CANCELLED->value, 'cancellation_reason' => 'Property sold']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::CANCELLED->value);
    }

    public function test_agent_can_reschedule_viewing()
    {
        $viewing = PropertyViewing::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $newTime = now()->addDays(2)->setHour(16)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/viewings/{$viewing->id}/reschedule",
            [
                'status' => ViewingStatus::RESCHEDULED->value,
                'scheduled_at' => $newTime->toDateTimeString(),
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::RESCHEDULED->value);
    }

    public function test_agent_can_mark_viewing_completed()
    {
        $viewing = PropertyViewing::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/viewings/{$viewing->id}/complete"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::COMPLETED->value);
    }

    public function test_agent_can_mark_no_show()
    {
        $viewing = PropertyViewing::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/viewings/{$viewing->id}/no-show"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::NO_SHOW->value);
    }

    public function test_user_can_view_my_viewings()
    {
        PropertyViewing::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        PropertyViewing::factory()->count(2)->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/viewings/my');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_agent_can_view_calendar()
    {
        PropertyViewing::factory()->confirmed()->count(4)->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->agent)->getJson(
            '/api/dashboard/viewings/calendar?from='.now()->startOfWeek()->toDateString()
            .'&to='.now()->endOfWeek()->toDateString()
        );

        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    public function test_cannot_book_when_agent_overlaps()
    {
        $scheduledAt = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        PropertyViewing::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 30,
            'buffer_minutes' => 15,
        ]);

        $payload = [
            'property_id' => $this->property->id,
            'scheduled_at' => $scheduledAt->copy()->addMinutes(20)->toDateTimeString(),
            'duration_minutes' => 30,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/viewings', $payload);

        $response->assertStatus(500);
    }

    public function test_user_can_list_viewings()
    {
        PropertyViewing::factory()->count(5)->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/viewings');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_user_can_delete_own_viewing()
    {
        $viewing = PropertyViewing::factory()->pending()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            "/api/dashboard/viewings/{$viewing->id}"
        );

        $response->assertStatus(200);
        $this->assertSoftDeleted('property_viewings', ['id' => $viewing->id]);
    }

    public function test_user_cannot_complete_viewing_they_dont_own()
    {
        $otherUser = User::factory()->create();

        $viewing = PropertyViewing::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($otherUser)->patchJson(
            "/api/dashboard/viewings/{$viewing->id}/complete"
        );

        $response->assertStatus(403);
    }
}
