<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Traits;

use Brackets\AdminAuth\Activation\Notifications\ActivationNotification;
use Illuminate\Container\Container;

trait CanActivate
{
    /**
     * Get the e-mail address where activation links are sent.
     */
    public function getEmailForActivation(): string
    {
        return $this->email;
    }

    /**
     * Send the password reset notification.
     */
    public function sendActivationNotification(string $token): void
    {
        $this->notify(Container::getInstance()->make(ActivationNotification::class, ['token' => $token]));
    }
}
