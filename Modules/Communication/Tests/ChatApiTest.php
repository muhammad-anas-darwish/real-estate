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

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->recipient = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Chat API Tests', function () {
    test('can get chat rooms', function () {
        ChatRoom::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/chat/rooms');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    });

    test('can create private chat room', function () {
        $response = $this->postJson('/api/v1/chat/rooms', [
            'type' => 'private',
            'recipient_id' => $this->recipient->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'type', 'participants'],
        ]);

        $this->assertDatabaseHas('chat_rooms', [
            'type' => 'private',
        ]);
    });

    test('cannot create chat with non-existent user', function () {
        $response = $this->postJson('/api/v1/chat/rooms', [
            'type' => 'private',
            'recipient_id' => 9999,
        ]);

        $response->assertStatus(404);
    });

    test('can create property chat room', function () {
        $property = Property::factory()->create([
            'publisher_id' => $this->recipient->id,
        ]);

        $response = $this->postJson('/api/v1/chat/rooms', [
            'type' => 'property',
            'property_id' => $property->id,
        ]);

        $response->assertStatus(201);
    });

    test('cannot create property chat as agent', function () {
        $property = Property::factory()->create([
            'publisher_id' => $this->user->id,
        ]);

        $response = $this->postJson('/api/v1/chat/rooms', [
            'type' => 'property',
            'property_id' => $property->id,
        ]);

        $response->assertStatus(400);
    });

    test('returns existing room for duplicate private chat', function () {
        $room = ChatRoom::factory()->create(['type' => 'private']);
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        $response = $this->postJson('/api/v1/chat/rooms', [
            'type' => 'private',
            'recipient_id' => $this->recipient->id,
        ]);

        $response->assertStatus(201);
    });

    test('can get room details', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        $response = $this->getJson("/api/v1/chat/rooms/{$room->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['id', 'type', 'participants'],
        ]);
    });

    test('can get room messages', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);
        Message::factory()->count(5)->create(['room_id' => $room->id]);

        $response = $this->getJson("/api/v1/chat/rooms/{$room->id}/messages");

        $response->assertStatus(200);
    });

    test('can send message', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'body' => 'Test message',
            'type' => 'text',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'body', 'sender'],
        ]);
    });

    test('can send message with reply', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);
        $parentMessage = Message::factory()->create([
            'room_id' => $room->id,
            'sender_id' => $this->recipient->id,
        ]);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'body' => 'Reply message',
            'type' => 'text',
            'parent_id' => $parentMessage->id,
        ]);

        $response->assertStatus(201);
    });

    test('can delete own message', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);
        $message = Message::factory()->create([
            'room_id' => $room->id,
            'sender_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/chat/rooms/{$room->id}/messages/{$message->id}");

        $response->assertStatus(200);
    });

    test('cannot delete others message', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);
        $message = Message::factory()->create([
            'room_id' => $room->id,
            'sender_id' => $this->recipient->id,
        ]);

        $response = $this->deleteJson("/api/v1/chat/rooms/{$room->id}/messages/{$message->id}");

        $response->assertStatus(403);
    });

    test('can send typing indicator', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        Event::fake([UserTypingEvent::class]);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/typing");

        $response->assertStatus(200);
    });

    test('typing indicator is rate limited', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/typing");
        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/typing");

        $response->assertStatus(429);
    });
});

describe('Chat Broadcasting Tests', function () {
    test('message sent broadcasts event', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->recipient->id]);

        Event::fake([MessageSentEvent::class]);

        $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'body' => 'Test broadcast',
            'type' => 'text',
        ]);

        Event::assertDispatched(MessageSentEvent::class);
    });
});

describe('Chat Authorization Tests', function () {
    test('non-participant cannot get room messages', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->recipient->id]);

        $response = $this->getJson("/api/v1/chat/rooms/{$room->id}/messages");

        $response->assertStatus(403);
    });

    test('non-participant cannot send message', function () {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->recipient->id]);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'body' => 'Test',
            'type' => 'text',
        ]);

        $response->assertStatus(403);
    });
});