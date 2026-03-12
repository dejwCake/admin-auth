<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Providers;

use Brackets\AdminAuth\Activation\Brokers\ActivationBrokerFactory;
use Brackets\AdminAuth\Activation\Contracts\ActivationBrokerFactory as ActivationBrokerFactoryContract;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Override;

class ActivationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publish();
        }
    }

    /**
     * Register the service provider.
     */
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/activation.php', 'activation');

        $this->registerActivationBroker();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    #[Override]
    public function provides(): array
    {
        return ['auth.activation', 'auth.activation.broker'];
    }

    /**
     * Register the password broker instance.
     */
    protected function registerActivationBroker(): void
    {
        $this->app->bind(ActivationBrokerFactoryContract::class, ActivationBrokerFactory::class);
        $this->app->singleton('auth.activation', static fn ($app) => new ActivationBrokerFactory($app));

        $this->app->bind('auth.activation.broker', static fn ($app) => $app->make('auth.activation')->broker());
    }

    private function publish(): void
    {
        $timestamp = sprintf('%s_000000', date('Y_m_d'));

        $this->publishes([
            __DIR__ . '/../../../config/activation.php' => $this->app->configPath('activation.php'),
        ], 'config');

        if (!glob($this->app->basePath('database/migrations/*_create_activations_table.php'))) {
            $this->publishes([
                __DIR__ . '/../../../database/migrations/create_activations_table.php' =>
                    sprintf('%s/%s_create_activations_table.php', $this->app->databasePath('migrations'), $timestamp),
            ], 'migrations');
        }
    }
}
