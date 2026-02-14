<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $resetUrl
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject(Lang::get('passwords.notification_subject'))
            ->line(Lang::get('passwords.notification_reason'))
            ->action(Lang::get('passwords.notification_action'), $this->resetUrl)
            ->line(Lang::get('passwords.notification_expire', ['count' => $expireMinutes]))
            ->line(Lang::get('passwords.notification_no_action'));
    }
}
