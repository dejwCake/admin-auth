<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Activation\Providers;

use Brackets\AdminAuth\Activation\Brokers\ActivationBrokerFactory;
use Brackets\AdminAuth\Activation\Contracts\ActivationBrokerFactory as ActivationBrokerFactoryContract;
use Brackets\AdminAuth\Activation\Facades\Activation;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

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
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/activation.php', 'activation');

        $this->registerActivationBroker();

        $loader = AliasLoader::getInstance();
        $loader->alias('Activation', Activation::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
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
        $time = date('His', time());
        $this->publishes([
            __DIR__ . '/../../../config/activation.php' => $this->app->configPath('activation.php'),
        ], 'config');

        if (!glob($this->app->basePath('database/migrations/*_create_activations_table.php'))) {
            $this->publishes([
                __DIR__ . '/../../../database/migrations/create_activations_table.php' =>
                    $this->app->databasePath('migrations') . '/2025_01_01_' . $time . '_create_activations_table.php',
            ], 'migrations');
        }
    }
}
