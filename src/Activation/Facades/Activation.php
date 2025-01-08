<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Facades;

use Brackets\AdminAuth\Activation\Contracts\ActivationBroker as ActivationBrokerContract;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Brackets\AdminAuth\Activation\Brokers\ActivationBrokerManager
 * @method static ActivationBrokerContract broker(?string $name = null)
 */
class Activation extends Facade
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
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'auth.activation';
    }
}
