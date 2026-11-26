<?php

declare(strict_types=1);

namespace Brackets\AdminAuth;

use Brackets\AdminAuth\Activation\Providers\ActivationServiceProvider;
use Brackets\AdminAuth\Console\Commands\AdminAuthInstall;
use Brackets\AdminAuth\Console\Commands\TwoFactorResetCommand;
use Brackets\AdminAuth\Exceptions\Handler;
use Brackets\AdminAuth\Http\Controllers\MissingRoutesController;
use Brackets\AdminAuth\Passkeys\PasskeySerializer;
use Brackets\AdminAuth\Providers\EventServiceProvider;
use Brackets\AdminAuth\Providers\MiddlewareServiceProvider;
use Brackets\AdminAuth\Services\SchemaCheck;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Override;

class AdminAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'brackets/admin-auth');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'brackets/admin-auth');

        $config = $this->app->make(Config::class);

        if ($config->get('admin-auth.use_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
            $this->loadRoutesFrom(__DIR__ . '/../routes/two-factor.php');
            $this->loadRoutesFrom(__DIR__ . '/../routes/passkeys.php');
            $this->loadRoutesFrom(__DIR__ . '/../routes/admin-security.php');
        }

        if (
            $config->get('admin-auth.use_routes', true)
            && $config->get('admin-auth.activations.self_activation_form_enabled', true)
        ) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/activation-form.php');
        }

        $router = $this->app->make(Router::class);
        if (!$router->has('login')) {
            $router->get('/login', [MissingRoutesController::class, 'redirect'])
                ->middleware(['web'])
                ->name('login');
        }
        if (!$router->has('register')) {
            $router->get('/register', [MissingRoutesController::class, 'redirect'])
                ->middleware(['web'])
                ->name('register');
        }

        // App routes load during the boot phase, so only after it is it known whether the app has its own profile.
        $this->app->booted(function (): void {
            $this->loadProfileRoutes();
        });

        if ($this->app->runningInConsole()) {
            $this->publish();
        }
    }

    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/admin-auth.php', 'admin-auth');

        $this->app->register(ActivationServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
        $this->app->register(MiddlewareServiceProvider::class);

        $this->app->singleton(PasskeySerializer::class);
        $this->app->scoped(SchemaCheck::class);

        $this->app->bind(ExceptionHandler::class, Handler::class);

        $this->commands([
            AdminAuthInstall::class,
            TwoFactorResetCommand::class,
        ]);
    }

    private function loadProfileRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $config = $this->app->make(Config::class);
        if (!$config->get('admin-auth.use_routes', true)) {
            return;
        }

        // true / false (also as 'true', 'false', '1', '0', …); anything else counts as 'auto'
        $enabled = filter_var(
            $config->get('admin-auth.profile.enabled', 'auto'),
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );
        if ($enabled === false) {
            return;
        }

        if ($enabled === null) {
            $router = $this->app->make(Router::class);
            $router->getRoutes()->refreshNameLookups();
            if ($router->has('admin/edit-profile')) {
                return;
            }
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/profile.php');
    }

    private function publish(): void
    {
        $timestamp = sprintf('%s_000000', date('Y_m_d'));

        $this->publishes([
            __DIR__ . '/../config/admin-auth.php' => $this->app->configPath('admin-auth.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/brackets/admin-auth'),
        ], 'lang');

        $this->publishes([
            __DIR__ . '/../resources/views' => $this->app->resourcePath('views/vendor/brackets/admin-auth'),
        ], 'views');

        if (!glob($this->app->databasePath('migrations/*_create_admin_activations_table.php'))) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_admin_activations_table.php'
                => sprintf(
                    '%s/%s_create_admin_activations_table.php',
                    $this->app->databasePath('migrations'),
                    $timestamp,
                ),
            ], 'migrations');
        }

        if (!glob($this->app->databasePath('migrations/*_create_admin_password_resets_table.php'))) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_admin_password_resets_table.php'
                => sprintf(
                    '%s/%s_create_admin_password_resets_table.php',
                    $this->app->databasePath('migrations'),
                    $timestamp,
                ),
            ], 'migrations');
        }

        if (!glob($this->app->databasePath('migrations/*_create_admin_users_table.php'))) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_admin_users_table.php'
                => sprintf('%s/%s_create_admin_users_table.php', $this->app->databasePath('migrations'), $timestamp),
            ], 'migrations');
        }

        if (!glob($this->app->databasePath('migrations/*_create_admin_two_factors_table.php'))) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_admin_two_factors_table.php'
                => sprintf(
                    '%s/%s_000001_create_admin_two_factors_table.php',
                    $this->app->databasePath('migrations'),
                    date('Y_m_d'),
                ),
            ], 'migrations');
        }

        if (!glob($this->app->databasePath('migrations/*_add_admin_security_reset_permission.php'))) {
            // After the admin-users and passkeys migrations of the same day; it only needs the permission tables.
            $this->publishes([
                __DIR__ . '/../database/migrations/add_admin_security_reset_permission.php'
                => sprintf(
                    '%s/%s_000003_add_admin_security_reset_permission.php',
                    $this->app->databasePath('migrations'),
                    date('Y_m_d'),
                ),
            ], 'migrations');
        }

        if (!glob($this->app->databasePath('migrations/*_create_admin_passkeys_table.php'))) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_admin_passkeys_table.php'
                => sprintf(
                    '%s/%s_000002_create_admin_passkeys_table.php',
                    $this->app->databasePath('migrations'),
                    date('Y_m_d'),
                ),
            ], 'migrations');
        }
    }
}
