<?php

declare(strict_types=1);

namespace Brackets\AdminAuth;

use Brackets\AdminAuth\Activation\Providers\ActivationServiceProvider;
use Brackets\AdminAuth\Console\Commands\AdminAuthInstall;
use Brackets\AdminAuth\Exceptions\Handler;
use Brackets\AdminAuth\Http\Controllers\MissingRoutesController;
use Brackets\AdminAuth\Http\Middleware\ApplyUserLocale;
use Brackets\AdminAuth\Http\Middleware\CanAdmin;
use Brackets\AdminAuth\Http\Middleware\RedirectIfAuthenticated;
use Brackets\AdminAuth\Providers\EventServiceProvider;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AdminAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'brackets/admin-auth');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'brackets/admin-auth');

        $config = app(Config::class);
        assert($config instanceof Config);

        if ($config->get('admin-auth.use_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        }

        if (
            $config->get('admin-auth.use_routes', true)
            && $config->get('admin-auth.activations.self_activation_form_enabled', true)
        ) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/activation-form.php');
        }

        $router = app(Router::class);
        //This is just because laravel does not provide it by default,
        // however expect in AuthenticationException that it exists
        if (!$router->has('login')) {
            $router->get('/login', [MissingRoutesController::class, 'redirect'])
                ->middleware(['web'])
                ->name('login');
        }
        //This is just because in welcome.blade.php someone was lazy to check
        // if also register route exists and ask only for login
        if (!$router->has('register')) {
            $router->get('/register', [MissingRoutesController::class, 'redirect'])
                ->middleware(['web'])
                ->name('register');
        }

        if ($this->app->runningInConsole()) {
            $this->publish();
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../install-stubs/config/admin-auth.php', 'admin-auth');

        $this->app->register(ActivationServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        $this->app->bind(ExceptionHandler::class, Handler::class);

        app(Router::class)->pushMiddlewareToGroup('admin', CanAdmin::class);
        app(Router::class)->pushMiddlewareToGroup('admin', ApplyUserLocale::class);
        app(Router::class)->aliasMiddleware('guest.admin', RedirectIfAuthenticated::class);

        $this->commands([
            AdminAuthInstall::class,
        ]);
    }

    private function publish(): void
    {
        $time = date('His', time());
        $this->publishes([
            __DIR__ . '/../install-stubs/config/admin-auth.php' => config_path('admin-auth.php'),
        ], 'config');

        if (!glob(base_path('database/migrations/*_create_admin_activations_table.php'))) {
            $this->publishes([
                __DIR__ . '/../install-stubs/database/migrations/create_admin_activations_table.php'
                => database_path('migrations') . '/2025_01_01_' . $time . '_create_admin_activations_table.php',
            ], 'migrations');
        }

        if (!glob(base_path('database/migrations/*_create_admin_password_resets_table.php'))) {
            $this->publishes([
                __DIR__ . '/../install-stubs/database/migrations/create_admin_password_resets_table.php'
                => database_path('migrations') . '/2025_01_01_' . $time . '_create_admin_password_resets_table.php',
            ], 'migrations');
        }

        if (!glob(base_path('database/migrations/*_create_admin_users_table.php'))) {
            $this->publishes([
                __DIR__ . '/../install-stubs/database/migrations/create_admin_users_table.php'
                => database_path('migrations') . '/2025_01_01_' . $time . '_create_admin_users_table.php',
            ], 'migrations');
        }

        $time = date('His', time() + 1);
        if (!glob(base_path('database/migrations/*_add_last_login_at_timestamp_to_admin_users_table.php'))) {
            $this->publishes([
                __DIR__
                . '/../install-stubs/database/migrations/add_last_login_at_timestamp_to_admin_users_table.php'
                => database_path('migrations',)
                    . '/2025_01_01_' . $time . '_add_last_login_at_timestamp_to_admin_users_table.php',
            ], 'migrations');
        }

        $this->publishes([
            __DIR__ . '/../install-stubs/lang' => lang_path('vendor/admin-auth'),
        ], 'lang');
    }
}
