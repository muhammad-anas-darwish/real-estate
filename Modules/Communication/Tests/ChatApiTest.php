<?php

namespace Modules\Communication\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\Communication\Entities\Message;
use Modules\Communication\Enums\RoomTypeEnum;
use Modules\Communication\Events\MessageSentEvent;
use Modules\Communication\Events\UserTypingEvent;
use Modules\Communication\Services\ChatService;
use Modules\RealEstate\Entities\Property;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->recipient = User::factory()->create();
    }

    public function test_can_get_chat_rooms()
    {
        ChatRoom::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/chat/rooms');

        $response->assertStatus(200);
    }

    public function test_can_create_private_chat_room()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/chat/rooms', [
                'type' => 'private',
                'recipient_id' => $this->recipient->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('chat_rooms', [
            'type' => 'private',
        ]);
    }

    public function test_cannot_create_chat_with_non_existent_user()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/chat/rooms', [
                'type' => 'private',
                'recipient_id' => 9999,
            ]);

        $response->assertStatus(404);
    }

    public function test_can_create_property_chat_room()
    {
        $country = \Modules\Core\SubModules\Location\Entities\Country::factory()->create();
        $city = \Modules\Core\SubModules\Location\Entities\City::factory()->create(['country_id' => $country->id]);
        
        $property = \Modules\RealEstate\Entities\Property::factory()->create([
            'publisher_id' => $this->recipient->id,
            'city_id' => $city->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/chat/rooms', [
                'type' => 'property',
                'property_id' => $property->id,
            ]);

        $response->assertStatus(201);
    }

    public function test_get_room_details()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/chat/rooms/{$room->id}");

        $response->assertStatus(200);
    }

    public function test_can_get_room_messages()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);
        Message::factory()->count(5)->create(['room_id' => $room->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/chat/rooms/{$room->id}/messages");

        $response->assertStatus(200);
    }

    public function test_can_send_message()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/chat/rooms/{$room->id}/messages", [
                'body' => 'Test message',
                'type' => 'text',
            ]);

        $response->assertStatus(201);
    }

    public function test_can_delete_own_message()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);
        $message = Message::factory()->create([
            'room_id' => $room->id,
            'sender_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/chat/rooms/{$room->id}/messages/{$message->id}");

        $response->assertStatus(200);
    }

    public function test_cannot_delete_others_message()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);
        $message = Message::factory()->create([
            'room_id' => $room->id,
            'sender_id' => $this->recipient->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/chat/rooms/{$room->id}/messages/{$message->id}");

        $response->assertStatus(403);
    }

    public function test_can_send_typing_indicator()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/chat/rooms/{$room->id}/typing");

        $response->assertStatus(200);
    }

    public function test_message_sent_broadcasts_event()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        Event::fake([MessageSentEvent::class]);

        $this->actingAs($this->user)
            ->postJson("/chat/rooms/{$room->id}/messages", [
                'body' => 'Test broadcast',
                'type' => 'text',
            ]);

        Event::assertDispatched(MessageSentEvent::class);
    }

    public function test_non_participant_cannot_get_room_messages()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->recipient->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/chat/rooms/{$room->id}/messages");

        $response->assertStatus(403);
    }

    public function test_non_participant_cannot_send_message()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->recipient->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/chat/rooms/{$room->id}/messages", [
                'body' => 'Test',
                'type' => 'text',
            ]);

        $response->assertStatus(403);
    }
}