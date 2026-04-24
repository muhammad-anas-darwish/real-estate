<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\Communication\Services\Broadcasting\ChannelAuthService;

Broadcast::channel('user.{userId}', function ($user, int $userId) {
    return app(ChannelAuthService::class)->authorizeUserChannel($user, $userId);
});

Broadcast::channel('chat.{roomId}', function ($user, int $roomId) {
    return app(ChannelAuthService::class)->authorizeChatChannel($user, $roomId);
});

Broadcast::channel('property.{propertyId}', function ($user, int $propertyId) {
    return app(ChannelAuthService::class)->authorizePropertyChannel($user, $propertyId);
});