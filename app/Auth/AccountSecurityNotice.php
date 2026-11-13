<?php

namespace App\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountSecurityNotice extends Notification
{
    public function __construct(private string $message) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Temp Mail account security')
            ->line($this->message)->line('If you did not make this change, contact support.');
    }
}
