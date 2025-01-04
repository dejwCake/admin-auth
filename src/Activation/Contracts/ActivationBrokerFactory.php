<?php

namespace Brackets\AdminAuth\Activation\Contracts;

interface ActivationBrokerFactory
{
    /**
     * Get a password broker instance by name.
     */
    public function broker(?string $name = null): ?ActivationBroker;
}
