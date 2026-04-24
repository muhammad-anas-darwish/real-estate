<?php

namespace Modules\Communication\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Kreait\Laravel\Firebase\Facades\FirebaseMessaging;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserFcmToken;
use Modules\Communication\Enums\DeviceTypeEnum;
use Modules\Communication\Events\NotificationReceivedEvent;
use Modules\Communication\Notifications\NewMessageNotification;
use Modules\Communication\Services\Fcm\FcmPayload;
use Modules\Communication\Services\Fcm\FcmResult;
use Modules\Communication\Services\Fcm\FcmService;
use Modules\Communication\Services\Fcm\FcmTokenService;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('FCM Notification Tests', function () {
    test('fcm sends notification with correct payload structure', function () {
        $token = UserFcmToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => 'valid_device_token',
            'device_type' => DeviceTypeEnum::ANDROID,
        ]);

        $mockMessaging = Mockery::mock(\Kreait\Firebase\Messaging::class);
        $mockMulticast = Mockery::mock(\Kreait\Firebase\Messaging\MulticastSendReport::class);

        $mockMessaging->shouldReceive('createMulticast')->andReturn($mockMessaging);
        $mockMessaging->shouldReceive('addRecipientWithToken')->with('valid_device_token');
        $mockMessaging->shouldReceive('sendMulticast')->andReturn($mockMulticast);

        $mockMulticast->shouldReceive('successes')->andReturn(new class {
            public function count() { return 1; }
        });
        $mockMulticast->shouldReceive('failures')->andReturn(new class {
            public function items() { return []; }
        });

        $fcmService = new FcmService($mockMessaging, new FcmTokenService());
        $payload = new FcmPayload('Test Title', 'Test Body', ['key' => 'value']);

        $result = $fcmService->sendToUser($this->user, $payload);

        expect($result->successCount)->toBe(1);
        expect($result->failureCount)->toBe(0);
    });

    test('fcm removes failed invalid tokens', function () {
        $token = UserFcmToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => 'invalid_token',
            'device_type' => DeviceTypeEnum::ANDROID,
        ]);

        $mockMessaging = Mockery::mock(\Kreait\Firebase\Messaging::class);
        $mockMulticast = Mockery::mock(\Kreait\Firebase\Messaging\MulticastSendReport::class);

        $mockMessaging->shouldReceive('createMulticast')->andReturn($mockMessaging);
        $mockMessaging->shouldReceive('addRecipientWithToken')->with('invalid_token');
        $mockMessaging->shouldReceive('sendMulticast')->andReturn($mockMulticast);

        $mockMulticast->shouldReceive('successes')->andReturn(new class { public function count() { return 0; } });
        $mockMulticast->shouldReceive('failures')->andReturn(new class {
            public function items() {
                return [
                    ['index' => 0, 'error' => 'NOT_FOUND']
                ];
            }
        });

        $fcmService = new FcmService($mockMessaging, new FcmTokenService());
        $payload = new FcmPayload('Test Title', 'Test Body');

        $result = $fcmService->sendToUser($this->user, $payload);

        expect($result->failureCount)->toBe(1);
        expect($result->failedTokens)->toContain('invalid_token');
    });
});

test('notification not sent when user has no fcm tokens', function () {
    $fcmService = new FcmService(
        Mockery::mock(\Kreait\Firebase\Messaging::class),
        new FcmTokenService()
    );

    $payload = new FcmPayload('Test Title', 'Test Body');

    $result = $fcmService->sendToUser($this->user, $payload);

    expect($result->successCount)->toBe(0);
    expect($result->failureCount)->toBe(0);
});
});

describe('Pusher Broadcasting Tests', function () {
    test('notification received event is fired with correct data', function () {
        Event::fake(NotificationReceivedEvent::class);

        $this->user->notify(new NewMessageNotification(
            senderName: 'Ahmed',
            messagePreview: 'Hello there!',
            roomId: 1
        ));

        Event::assertDispatched(NotificationReceivedEvent::class, function ($event) {
            return $event->payload['notification_type'] === NewMessageNotification::class
                && $event->payload['title'] === 'رسالة جديدة من Ahmed';
        });
    });

    test('notification sent to user broadcasts on user channel', function () {
        Event::fake(NotificationReceivedEvent::class);

        $this->user->notify(new NewMessageNotification(
            senderName: 'Ahmed',
            messagePreview: 'Test message',
            roomId: 5
        ));

        Event::assertDispatched(NotificationReceivedEvent::class);
    });
});