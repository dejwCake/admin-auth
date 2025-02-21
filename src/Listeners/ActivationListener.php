<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Listeners;

use Brackets\AdminAuth\Activation\Contracts\ActivationBrokerFactory;
use Brackets\AdminAuth\Activation\Contracts\CanActivate;
use Brackets\AdminAuth\Services\ActivationService;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Events\Dispatcher;
use Throwable;

class ActivationListener
{
    /**
     * Activation broker used for admin user
     */
    private string $activationBroker;

    public function __construct(
        private readonly ActivationBrokerFactory $activationBrokerFactory,
        private readonly Config $config,
        private readonly AuthManager $authManager,
    ) {
        $this->activationBroker = $this->config->get('admin-auth.defaults.activations', 'admin_users');
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $activationBrokerConfig = $this->config->get("activation.activations.{$this->activationBroker}");
        if ($this->authManager->createUserProvider($activationBrokerConfig['provider']) !== null) {
            try {
                $userClass = $this->activationBrokerFactory->broker($this->activationBroker)
                    ->getUserModelClass();
                if ($userClass === null) {
                    return;
                }

                $interfaces = class_implements($userClass);
                if ($interfaces && in_array(CanActivate::class, $interfaces, true)) {
                    $events->listen('eloquent.created: ' . $userClass, ActivationService::class);
                }
            } catch (Throwable) {
                //do nothing
            }

            //TODO listen on user edit and if email has changed, deactivate user and send email again
        }
    }
}
