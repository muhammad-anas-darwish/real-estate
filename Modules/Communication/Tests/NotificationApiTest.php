<?php

namespace Modules\Communication\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserFcmToken;
use Modules\Communication\Enums\DeviceTypeEnum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    public function test_get_notifications_returns_paginated_list()
    {
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

        $response = $this->actingAs($this->user)
            ->getJson('/notifications');

        $response->assertStatus(200);
    }

    public function test_notifications_sorted_unread_first()
    {
        $this->user->notifications()->create([
            'id' => 'read-notif',
            'type' => 'test',
            'data' => ['title' => 'Read'],
            'read_at' => now(),
        ]);
        $this->user->notifications()->create([
            'id' => 'unread-notif',
            'type' => 'test',
            'data' => ['title' => 'Unread'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/notifications');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertFalse($data[0]['is_read'] ?? true);
    }

    public function test_get_unread_count()
    {
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

        $response = $this->actingAs($this->user)
            ->getJson('/notifications/unread-count');

        $response->assertStatus(200);
        $response->assertJsonPath('data.unread_count', 2);
    }

    public function test_mark_single_notification_as_read()
    {
        $notification = $this->user->notifications()->create([
            'id' => 'mark-read-test',
            'type' => 'test',
            'data' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/notifications/mark-read-test/read');

        $response->assertStatus(200);
    }

    public function test_mark_all_notifications_as_read()
    {
        $this->user->notifications()->create(['id' => 'all-1', 'type' => 'test', 'data' => []]);
        $this->user->notifications()->create(['id' => 'all-2', 'type' => 'test', 'data' => []]);
        $this->user->notifications()->create(['id' => 'all-3', 'type' => 'test', 'data' => []]);

        $response = $this->actingAs($this->user)
            ->patchJson('/notifications/read-all');

        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    public function test_delete_notification()
    {
        $notification = $this->user->notifications()->create([
            'id' => 'delete-test',
            'type' => 'test',
            'data' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/notifications/delete-test');

        $response->assertStatus(200);
    }

    public function test_cannot_access_other_users_notifications()
    {
        $otherUser = User::factory()->create();
        $otherUser->notifications()->create([
            'id' => 'other-notif',
            'type' => 'test',
            'data' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/notifications');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_can_register_fcm_token()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/fcm/register', [
                'token' => 'test_device_token',
                'device_type' => 'android',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_fcm_tokens', [
            'user_id' => $this->user->id,
            'token' => 'test_device_token',
        ]);
    }

    public function test_cannot_register_with_invalid_device_type()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/fcm/register', [
                'token' => 'test_token',
                'device_type' => 'invalid',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_revoke_specific_token()
    {
        $token = UserFcmToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => 'revoke_test_token',
            'device_type' => DeviceTypeEnum::IOS,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/fcm/revoke', [
                'token' => 'revoke_test_token',
            ]);

        $response->assertStatus(200);
    }

    public function test_can_revoke_all_tokens()
    {
        UserFcmToken::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/fcm/revoke');

        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->fcmTokens()->count());
    }

    public function test_user_has_fcm_tokens()
    {
        UserFcmToken::factory()->count(2)->create(['user_id' => $this->user->id]);

        $this->assertTrue($this->user->hasActiveFcmTokens());
    }

    public function test_user_no_fcm_tokens()
    {
        $this->assertFalse($this->user->hasActiveFcmTokens());
    }

    public function test_route_notification_for_fcm_returns_tokens()
    {
        UserFcmToken::factory()->count(2)->create(['user_id' => $this->user->id]);

        $tokens = $this->user->routeNotificationForFcm();

        $this->assertEquals(2, $tokens->count());
    }
}