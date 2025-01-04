<?php

namespace Brackets\AdminAuth\Activation\Providers;

use Brackets\AdminAuth\Activation\Brokers\ActivationBrokerManager;
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
            $this->publishes([
                __DIR__ . '/../../../install-stubs/config/activation.php' => config_path('activation.php'),
            ], 'config');

            if (!glob(base_path('database/migrations/*_create_activations_table.php'))) {
                $this->publishes([
                    __DIR__ . '/../../../install-stubs/database/migrations/create_activations_table.php' => database_path('migrations') . '/2017_08_24_000000_create_activations_table.php',
                ], 'migrations');
            }
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../install-stubs/config/activation.php',
            'activation'
        );

        $this->registerActivationBroker();

        $loader = AliasLoader::getInstance();
        $loader->alias('Activation', Activation::class);
    }

    /**
     * Register the password broker instance.
     */
    protected function registerActivationBroker(): void
    {
        $this->app->singleton('auth.activation', function ($app) {
            return new ActivationBrokerManager($app);
        });

        $this->app->bind('auth.activation.broker', function ($app) {
            return $app->make('auth.activation')->broker();
        });
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
}
