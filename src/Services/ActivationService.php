<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

use Brackets\AdminAuth\Activation\Contracts\ActivationBroker;
use Brackets\AdminAuth\Activation\Contracts\ActivationBrokerFactory;
use Brackets\AdminAuth\Activation\Contracts\CanActivate;
use Illuminate\Contracts\Config\Repository as Config;
use Psr\Log\LoggerInterface;

final class ActivationService
{
    /**
     * Activation broker used for admin user
     */
    private string $activationBroker;

    public function __construct(
        private readonly ActivationBrokerFactory $activationBrokerFactory,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {
        $this->activationBroker = $this->config->get('admin-auth.defaults.activations', 'admin_users');
    }

    /**
     * Handles activation creation after user created
     */
    public function handle(CanActivate $user): bool|string
    {
        if (!$this->config->get('admin-auth.activation_enabled')) {
            $this->logger->info('Activation disabled.');

            return false;
        }

        if (property_exists($user, 'activated') && $user->activated === true) {
            $this->logger->info('User is already activated.');

            return true;
        }

        // We will send the activation link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->activationBrokerFactory->broker($this->activationBroker)
            ->sendActivationLink($this->credentials($user));

        if ($response === ActivationBroker::ACTIVATION_LINK_SENT) {
            $this->logger->info('Activation e-mail has been send: ' . $response);
        } else {
            $this->logger->error('Sending activation e-mail has failed: ' . $response);
        }

        return $response;
    }

    /**
     * Get the needed authorization credentials from user.
     *
     * @return array<string, string|bool>
     */
    protected function credentials(CanActivate $user): array
    {
        return ['email' => $user->getEmailForActivation(), 'activated' => false];
    }
}
