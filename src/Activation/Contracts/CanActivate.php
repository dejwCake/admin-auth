<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Contracts;

interface CanActivate
{
    /**
     * Get the e-mail address where password reset links are sent.
     */
    public function getEmailForActivation(): string;

    /**
     * Send the password reset notification.
     */
    public function sendActivationNotification(string $token): void;
}
