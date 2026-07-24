<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Enums\OtpPurpose;

class OtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly OtpPurpose $purpose,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('auth.otp.mail_subject'))
            ->greeting(__('auth.otp.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('auth.otp.mail_line'))
            ->line($this->code)
            ->line(__('auth.otp.mail_expiry', ['minutes' => config('auth.otp.expiry_minutes', 5)]))
            ->salutation(__('auth.otp.mail_salutation'));
    }
}
