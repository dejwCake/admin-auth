<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Notifications;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivationNotification extends Notification
{
    public function __construct(private readonly string $token, private readonly UrlGenerator $urlGenerator,)
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
        return (new MailMessage())
            ->subject(trans('brackets/admin-auth::activations.email.action'))
            ->markdown('brackets/admin-auth::admin.auth.emails.activation', [
                'actionUrl' => $this->urlGenerator->route(
                    'brackets/admin-auth::admin/activation/activate',
                    ['token' => $this->token],
                ),
            ]);
    }
}
