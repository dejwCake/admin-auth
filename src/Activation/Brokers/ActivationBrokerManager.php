<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Brokers;

use Brackets\AdminAuth\Activation\Contracts\ActivationBroker as ActivationBrokerContract;
use Brackets\AdminAuth\Activation\Contracts\ActivationBrokerFactory as FactoryContract;
use Brackets\AdminAuth\Activation\Repositories\DatabaseTokenRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ActivationBrokerManager implements FactoryContract
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The array of created "drivers".
     *
     * @var array<string, ActivationBrokerContract>
     */
    protected array $brokers = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function __call(string $method, array $parameters)
    {
        return $this->broker()->{$method}(...$parameters);
    }

    /**
     * Attempt to get the broker from the local cache.
     */
    public function broker(?string $name = null): ActivationBrokerContract
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->brokers[$name] ?? $this->brokers[$name] = $this->resolve($name);
    }

    /**
     * Get the default activation broker name.
     */
    public function getDefaultDriver(): string
    {
        return $this->app['config']['activation.defaults.activations'];
    }

    /**
     * Set the default activation broker name.
     */
    public function setDefaultDriver(string $name): void
    {
        $this->app['config']['activation.defaults.activations'] = $name;
    }

    /**
     * Resolve the given broker.
     *
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name): ActivationBrokerContract
    {
        $config = $this->getConfig($name);

        if ($config === null) {
            throw new InvalidArgumentException("Activationer [{$name}] is not defined.");
        }

        // The password broker uses a token repository to validate tokens and send user
        // password e-mails, as well as validating that password reset process as an
        // aggregate service of sorts providing a convenient interface for resets.
        return new ActivationBroker(
            $this->createTokenRepository($config),
            $this->app['auth']->createUserProvider($config['provider']),
        );
    }

    /**
     * Create a token repository instance based on the given configuration.
     *
     * @param array<string, string|int> $config
     */
    protected function createTokenRepository(array $config): DatabaseTokenRepository
    {
        $key = $this->app['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        $connection = $config['connection'] ?? null;

        return new DatabaseTokenRepository(
            $this->app['db']->connection($connection),
            $this->app['hash'],
            $config['table'],
            $key,
            $config['expire'],
        );
    }

    /**
     * Get the activation broker configuration.
     *
     * @return array<string, string|int>|null
     */
    protected function getConfig(string $name): ?array
    {
        return $this->app['config']["activation.activations.{$name}"];
    }
}
