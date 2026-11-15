<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Providers;

use Brackets\AdminAuth\Http\Middleware\ApplyUserLocale;
use Brackets\AdminAuth\Http\Middleware\CanAdmin;
use Brackets\AdminAuth\Http\Middleware\EnsureTwoFactorAuthenticated;
use Brackets\AdminAuth\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\ServiceProvider;
use Override;

/**
 * Fills the `admin` middleware group and registers the `guest.admin` alias.
 */
final class MiddlewareServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('admin', CanAdmin::class);
        $router->pushMiddlewareToGroup('admin', ApplyUserLocale::class);
        // Ends the admin's other sessions once their password changes.
        $router->pushMiddlewareToGroup('admin', AuthenticateSession::class);
        // Holds the admin on the two-factor challenge, or on enrolment when two-factor is required.
        $router->pushMiddlewareToGroup('admin', EnsureTwoFactorAuthenticated::class);
        $router->aliasMiddleware('guest.admin', RedirectIfAuthenticated::class);
    }
}
