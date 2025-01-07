<?php

namespace Brackets\AdminAuth\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivationNotification extends Notification
{
    /**
     * The password reset token.
     */
    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        //TODO change to template?
        return (new MailMessage)
            ->line(trans('brackets/admin-auth::activations.email.line'))
            ->action(trans('brackets/admin-auth::activations.email.action'), route('brackets/admin-auth::admin/activation/activate', $this->token))
            ->line(trans('brackets/admin-auth::activations.email.notRequested'));
    }
}
