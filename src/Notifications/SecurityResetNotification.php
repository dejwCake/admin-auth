<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an admin that another admin removed their second factors.
 */
final class SecurityResetNotification extends Notification
{
    public function __construct(private readonly string $actorName, private readonly CarbonInterface $resetAt)
    {
    }

    /**
     * @return array<int, string>
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function toMail(object $notifiable): MailMessage
    {
        $replace = ['name' => $this->actorName, 'time' => $this->resetAt->isoFormat('LLL')];

        return (new MailMessage())
            ->subject(trans('brackets/admin-auth::admin.security_reset.notification.subject'))
            ->line(trans('brackets/admin-auth::admin.security_reset.notification.line', $replace))
            ->line(trans('brackets/admin-auth::admin.security_reset.notification.enrol'))
            ->line(trans('brackets/admin-auth::admin.security_reset.notification.unexpected', $replace));
    }
}
