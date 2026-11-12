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
use Illuminate\Session\Middleware\AuthenticateSession;
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

        $this->app->bind(ExceptionHandler::class, Handler::class);

        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('admin', CanAdmin::class);
        $router->pushMiddlewareToGroup('admin', ApplyUserLocale::class);
        // Ends the admin's other sessions once their password changes.
        $router->pushMiddlewareToGroup('admin', AuthenticateSession::class);
        $router->aliasMiddleware('guest.admin', RedirectIfAuthenticated::class);

        $this->commands([
            AdminAuthInstall::class,
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
    }
}
