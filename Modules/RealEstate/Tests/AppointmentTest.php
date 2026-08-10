<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\AppointmentType;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\ViewingStatus;
use Modules\RealEstate\Enums\ViewingType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $agent;

    protected Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin']);
        $permissions = [
            'appointments.list',
            'appointments.show',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',
            'appointments.confirm',
            'appointments.cancel',
            'appointments.reschedule',
            'appointments.complete',
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

        foreach ($permissions as $permission) {
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
            'type' => AppointmentType::VIEWING->value,
            'property_id' => $this->property->id,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'duration_minutes' => 30,
            'buffer_minutes' => 15,
            'viewing_type' => ViewingType::IN_PERSON->value,
            'contact_name' => 'John Doe',
            'contact_phone' => '123456789',
            'notes' => 'Looking forward to seeing the property',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/appointments', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', ViewingStatus::PENDING->value);
        $response->assertJsonPath('data.type', AppointmentType::VIEWING->value);
        $response->assertJsonPath('data.property_id', $this->property->id);
        $this->assertDatabaseHas('property_viewings', [
            'type' => AppointmentType::VIEWING->value,
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
        ]);
    }

    public function test_cannot_schedule_viewing_with_conflicting_time()
    {
        $scheduledAt = now()->addDay()->setHour(14)->setMinute(0)->setSecond(0);

        Appointment::factory()->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 30,
            'buffer_minutes' => 15,
            'status' => ViewingStatus::CONFIRMED,
        ]);

        $payload = [
            'type' => AppointmentType::VIEWING->value,
            'property_id' => $this->property->id,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'duration_minutes' => 30,
            'buffer_minutes' => 15,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/appointments', $payload);

        $response->assertStatus(500);
    }

    public function test_cannot_schedule_in_past()
    {
        $payload = [
            'type' => AppointmentType::VIEWING->value,
            'property_id' => $this->property->id,
            'scheduled_at' => now()->subHour()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/appointments', $payload);

        $response->assertStatus(422);
    }

    public function test_cannot_schedule_with_too_little_notice()
    {
        $payload = [
            'type' => AppointmentType::VIEWING->value,
            'property_id' => $this->property->id,
            'scheduled_at' => now()->addMinutes(30)->toDateTimeString(),
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/appointments', $payload);

        $response->assertStatus(500);
    }

    public function test_agent_can_confirm_appointment()
    {
        $appointment = Appointment::factory()->pending()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/appointments/{$appointment->id}/confirm"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::CONFIRMED->value);
        $this->assertDatabaseHas('property_viewings', [
            'id' => $appointment->id,
            'status' => ViewingStatus::CONFIRMED->value,
        ]);
    }

    public function test_agent_can_cancel_appointment()
    {
        $appointment = Appointment::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/appointments/{$appointment->id}/cancel",
            ['status' => ViewingStatus::CANCELLED->value, 'cancellation_reason' => 'Property sold']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::CANCELLED->value);
    }

    public function test_agent_can_reschedule_appointment()
    {
        $appointment = Appointment::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $newTime = now()->addDays(2)->setHour(16)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/appointments/{$appointment->id}/reschedule",
            [
                'status' => ViewingStatus::RESCHEDULED->value,
                'scheduled_at' => $newTime->toDateTimeString(),
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::RESCHEDULED->value);
    }

    public function test_agent_can_mark_appointment_completed()
    {
        $appointment = Appointment::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/appointments/{$appointment->id}/complete"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::COMPLETED->value);
    }

    public function test_agent_can_mark_no_show()
    {
        $appointment = Appointment::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->agent)->patchJson(
            "/api/dashboard/appointments/{$appointment->id}/no-show"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', ViewingStatus::NO_SHOW->value);
    }

    public function test_user_can_view_my_appointments()
    {
        Appointment::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        Appointment::factory()->count(2)->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/appointments/my');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_agent_can_view_calendar()
    {
        Appointment::factory()->confirmed()->count(4)->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->agent)->getJson(
            '/api/dashboard/appointments/calendar?from='.now()->startOfWeek()->toDateString()
            .'&to='.now()->endOfWeek()->toDateString()
        );

        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    public function test_user_can_list_appointments()
    {
        Appointment::factory()->count(5)->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/appointments');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_user_can_delete_own_appointment()
    {
        $appointment = Appointment::factory()->pending()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            "/api/dashboard/appointments/{$appointment->id}"
        );

        $response->assertStatus(200);
        $this->assertSoftDeleted('property_viewings', ['id' => $appointment->id]);
    }

    public function test_user_cannot_complete_appointment_they_dont_own()
    {
        $otherUser = User::factory()->create();

        $appointment = Appointment::factory()->confirmed()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($otherUser)->patchJson(
            "/api/dashboard/appointments/{$appointment->id}/complete"
        );

        $response->assertStatus(403);
    }

    public function test_viewing_without_property_fails_validation()
    {
        $payload = [
            'type' => AppointmentType::VIEWING->value,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/appointments', $payload);

        $response->assertStatus(422);
    }

    public function test_follow_up_without_followable_fails_validation()
    {
        $payload = [
            'type' => AppointmentType::FOLLOW_UP->value,
            'property_id' => null,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->user)->postJson('/api/dashboard/appointments/follow-ups', $payload);

        $response->assertStatus(422);
    }

    public function test_scope_of_type_filters_appointments()
    {
        Appointment::factory()->count(2)->viewing()->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        Appointment::factory()->count(3)->followUp()->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        $this->assertSame(2, Appointment::ofType(AppointmentType::VIEWING)->count());
        $this->assertSame(3, Appointment::ofType('follow_up')->count());
    }

    public function test_scope_for_user_returns_user_related_appointments()
    {
        Appointment::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        Appointment::factory()->count(3)->create([
            'user_id' => $this->agent->id,
            'property_id' => $this->property->id,
            'agent_id' => $this->user->id,
        ]);

        $this->assertSame(5, Appointment::forUser($this->user->id)->count());
    }

    public function test_backward_compat_viewings_endpoint_still_works()
    {
        Appointment::factory()->count(3)->create([
            'property_id' => $this->property->id,
            'agent_id' => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/viewings');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }
}
