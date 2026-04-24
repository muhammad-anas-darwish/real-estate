<?php

namespace Modules\Communication\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserFcmToken;
use Modules\Communication\Enums\DeviceTypeEnum;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Notification API Tests', function () {
    test('get notifications returns paginated list', function () {
        $this->user->notifications()->create([
            'id' => 'notif-1',
            'type' => 'test',
            'data' => ['title' => 'Test 1'],
        ]);
        $this->user->notifications()->create([
            'id' => 'notif-2',
            'type' => 'test',
            'data' => ['title' => 'Test 2'],
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    });

    test('notifications sorted unread first', function () {
        $read = $this->user->notifications()->create([
            'id' => 'read-notif',
            'type' => 'test',
            'data' => ['title' => 'Read'],
            'read_at' => now(),
        ]);
        $unread = $this->user->notifications()->create([
            'id' => 'unread-notif',
            'type' => 'test',
            'data' => ['title' => 'Unread'],
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data[0]['is_read'])->toBeFalse();
    });

    test('get unread count', function () {
        $this->user->notifications()->create([
            'id' => 'unread-1',
            'type' => 'test',
            'data' => [],
        ]);
        $this->user->notifications()->create([
            'id' => 'unread-2',
            'type' => 'test',
            'data' => [],
        ]);

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200);
        $response->assertJsonPath('unread_count', 2);
    });

    test('mark single notification as read', function () {
        $notification = $this->user->notifications()->create([
            'id' => 'mark-read-test',
            'type' => 'test',
            'data' => [],
        ]);

        $response = $this->patchJson('/api/v1/notifications/mark-read-test/read');

        $response->assertStatus(200);
        expect($notification->fresh()->read_at)->not->toBeNull();
    });

    test('mark all notifications as read', function () {
        $this->user->notifications()->create(['id' => 'all-1', 'type' => 'test', 'data' => []]);
        $this->user->notifications()->create(['id' => 'all-2', 'type' => 'test', 'data' => []]);
        $this->user->notifications()->create(['id' => 'all-3', 'type' => 'test', 'data' => []]);

        $response = $this->patchJson('/api/v1/notifications/read-all');

        $response->assertStatus(200);
        expect($this->user->unreadNotifications()->count())->toBe(0);
    });

    test('delete notification', function () {
        $notification = $this->user->notifications()->create([
            'id' => 'delete-test',
            'type' => 'test',
            'data' => [],
        ]);

        $response = $this->deleteJson('/api/v1/notifications/delete-test');

        $response->assertStatus(200);
        expect($this->user->notifications()->find('delete-test'))->toBeNull();
    });

    test('cannot access other users notifications', function () {
        $otherUser = User::factory()->create();
        $otherUser->notifications()->create([
            'id' => 'other-notif',
            'type' => 'test',
            'data' => [],
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    });
});

describe('FCM Token API Tests', function () {
    test('can register fcm token', function () {
        $response = $this->postJson('/api/v1/fcm/register', [
            'token' => 'test_device_token',
            'device_type' => 'android',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_fcm_tokens', [
            'user_id' => $this->user->id,
            'token' => 'test_device_token',
        ]);
    });

    test('cannot register with invalid device type', function () {
        $response = $this->postJson('/api/v1/fcm/register', [
            'token' => 'test_token',
            'device_type' => 'invalid',
        ]);

        $response->assertStatus(422);
    });

    test('can revoke specific token', function () {
        $token = UserFcmToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => 'revoke_test_token',
            'device_type' => DeviceTypeEnum::IOS,
        ]);

        $response = $this->deleteJson('/api/v1/fcm/revoke', [
            'token' => 'revoke_test_token',
        ]);

        $response->assertStatus(200);
        expect($this->user->fcmTokens()->where('token', 'revoke_test_token')->exists())->toBeFalse();
    });

    test('can revoke all tokens', function () {
        UserFcmToken::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson('/api/v1/fcm/revoke');

        $response->assertStatus(200);
        expect($this->user->fcmTokens()->count())->toBe(0);
    });

    test('can update existing token', function () {
        UserFcmToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => 'existing_token',
            'device_type' => DeviceTypeEnum::ANDROID,
        ]);

        $response = $this->postJson('/api/v1/fcm/register', [
            'token' => 'existing_token',
            'device_type' => 'ios',
        ]);

        $response->assertStatus(200);
    });
});

describe('Notification Preference Tests', function () {
    test('user has fcm tokens', function () {
        UserFcmToken::factory()->count(2)->create(['user_id' => $this->user->id]);

        expect($this->user->hasActiveFcmTokens())->toBeTrue();
    });

    test('user no fcm tokens', function () {
        expect($this->user->hasActiveFcmTokens())->toBeFalse();
    });

    test('route notification for fcm returns tokens', function () {
        UserFcmToken::factory()->count(2)->create(['user_id' => $this->user->id]);

        $tokens = $this->user->routeNotificationForFcm();

        expect($tokens)->toHaveCount(2);
    });
});

describe('Rate Limiting Tests', function () {
    test('notifications has rate limit', function () {
        $response = $this->getJson('/api/v1/notifications');
        expect($response->headers->has('X-RateLimit-Limit'))->toBeTrue();
    });

    test('typing has strict rate limit', function () {
        $room = \Modules\Communication\Entities\ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, User::factory()->id]);

        $this->postJson("/api/v1/chat/rooms/{$room->id}/typing");
        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/typing");

        $response->assertStatus(429);
    });
});