<?php

namespace Brackets\AdminAuth\Services;

use Brackets\AdminAuth\Activation\Contracts\ActivationBroker as ActivationBrokerContract;
use Brackets\AdminAuth\Activation\Contracts\CanActivate as CanActivateContract;
use Brackets\AdminAuth\Activation\Facades\Activation;
use Illuminate\Support\Facades\Log;

class ActivationService
{
    /**
     * Activation broker used for admin user
     */
    protected string $activationBroker = 'admin_users';

    /**
     * ActivationService constructor.
     */
    public function __construct()
    {
        $this->activationBroker = config('admin-auth.defaults.activations');
    }

    /**
     * Handles activation creation after user created
     */
    public function handle(CanActivateContract $user): bool|string
    {
        if (!config('admin-auth.activation_enabled')) {
            Log::info('Activation disabled.');
            return false;
        }

        if (property_exists($user, 'activated') && $user->activated === true) {
            Log::info('User is already activated.');
            return true;
        }

        // We will send the activation link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendActivationLink(
            $this->credentials($user)
        );

        if ($response === Activation::ACTIVATION_LINK_SENT) {
            Log::info('Activation e-mail has been send: ' . $response);
        } else {
            Log::error('Sending activation e-mail has failed: ' . $response);
        }

        return $response;
    }

    /**
     * Get the needed authorization credentials from user.
     *
     * @return array<string, string|bool>
     */
    protected function credentials(CanActivateContract $user): array
    {
        return ['email' => $user->getEmailForActivation(), 'activated' => false];
    }

    /**
     * Get the broker to be used during activation.
     */
    public function broker(): ActivationBrokerContract
    {
        return Activation::broker($this->activationBroker);
    }
}
