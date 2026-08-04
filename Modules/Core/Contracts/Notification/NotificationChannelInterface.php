<?php

namespace Modules\Core\Contracts\Notification;

use Illuminate\Notifications\Notification;

interface NotificationChannelInterface
{
    public function send($notifiable, Notification $notification): void;

    public function supports(string $notificationType): bool;
}
