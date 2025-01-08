<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Contracts;

use Closure;

interface ActivationBroker
{
    /**
     * Constant representing a successfully sent reminder.
     */
    public const ACTIVATION_LINK_SENT = 'sent';

    /**
     * Constant representing a successfully reset password.
     */
    public const ACTIVATED = 'activated';

    /**
     * Constant representing the user not found response.
     */
    public const INVALID_USER = 'invalid-user';

    /**
     * Constant representing an invalid token.
     */
    public const INVALID_TOKEN = 'invalid-token';

    /**
     * Constant representing a disabled activation.
     */
    public const ACTIVATION_DISABLED = 'disabled';

    /**
     * Send activation link to a user.
     *
     * @param array<string, string> $credentials
     */
    public function sendActivationLink(array $credentials): string;

    /**
     * Activate user for the given token.
     *
     * @param array<string, string> $credentials
     */
    public function activate(array $credentials, Closure $callback): string;

    /**
     * Get the user model class implementation.
     */
    public function getUserModelClass(): ?string;
}
