<?php

namespace Modules\Communication\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserFcmToken;
use Modules\Communication\Notifications\NewMessageNotification;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = actingAs($this->user);
});

describe('Notification API Tests', function () {
    test('get notifications returns paginated list with unread first', function () {
        $readNotification = $this->user->notifications()->create([
            'id' => 'read-notification-id',
            'type' => 'test',
            'data' => ['title' => 'Read'],
            'read_at' => now(),
        ]);

        $unreadNotification = $this->user->notifications()->create([
            'id' => 'unread-notification-id',
            'type' => 'test',
            'data' => ['title' => 'Unread'],
        ]);

        $response = $this->token->getJson('/api/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

        $firstNotification = $response->json('data.0');
        expect($firstNotification['is_read'] ?? true)->toBeFalse();
    });

    test('mark single notification as read', function () {
        $notification = $this->user->notifications()->create([
            'id' => 'test-notification-id',
            'type' => 'test',
            'data' => ['title' => 'Test'],
        ]);

        $response = $this->token->patchJson('/api/notifications/test-notification-id/read');

        $response->assertStatus(200);
        expect($notification->fresh()->read_at)->not->toBeNull();
    });

    test('mark all notifications as read', function () {
        $this->user->notifications()->create(['id' => 'notif-1', 'type' => 'test', 'data' => []]);
        $this->user->notifications()->create(['id' => 'notif-2', 'type' => 'test', 'data' => []]);

        $response = $this->token->patchJson('/api/notifications/read-all');

        $response->assertStatus(200);
        expect($this->user->unreadNotifications()->count())->toBe(0);
    });

    test('get unread count returns correct count', function () {
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

        $response = $this->token->getJson('/api/notifications/unread-count');

        $response->assertStatus(200);
        $response->assertJsonPath('unread_count', 2);
    });

    test('delete notification', function () {
        $notification = $this->user->notifications()->create([
            'id' => 'delete-test-id',
            'type' => 'test',
            'data' => [],
        ]);

        $response = $this->token->deleteJson('/api/notifications/delete-test-id');

        $response->assertStatus(200);
        expect($this->user->notifications()->find('delete-test-id'))->toBeNull();
    });
});

describe('Channel Authorization Tests', function () {
    test('user cannot join another users private channel', function () {
        $otherUser = User::factory()->create();
        $channelAuthService = app(\Modules\Communication\Services\Broadcasting\ChannelAuthService::class);

        $result = $channelAuthService->authorizeUserChannel($otherUser, $this->user->id);

        expect($result)->toBeFalse();
    });

    test('user can join own private channel', function () {
        $channelAuthService = app(\Modules\Communication\Services\Broadcasting\ChannelAuthService::class);

        $result = $channelAuthService->authorizeUserChannel($this->user, $this->user->id);

        expect($result)->toBeTrue();
    });

    test('non-participant cannot join chat room channel', function () {
        $channelAuthService = app(\Modules\Communication\Services\Broadcasting\ChannelAuthService::class);

        $roomId = 999;
        $result = $channelAuthService->authorizeChatChannel($this->user, $roomId);

        expect($result)->toBeFalse();
    });
});