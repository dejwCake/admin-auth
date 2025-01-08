<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivationNotification extends Notification
{
    public function __construct(private string $token)
    {
    }

    /**
     * Get the notification's channels.
     *
     * @return array<int, string>
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function toMail(object $notifiable): MailMessage
    {
        //TODO change to template?
        return (new MailMessage())
            ->line(trans('brackets/admin-auth::activations.email.line'))
            ->action(
                trans('brackets/admin-auth::activations.email.action'),
                route('brackets/admin-auth::admin/activation/activate', $this->token),
            )
            ->line(trans('brackets/admin-auth::activations.email.notRequested'));
    }
}
