<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Providers;

use Brackets\AdminAuth\Listeners\ActivationListener;
use Brackets\AdminAuth\Listeners\TwoFactorLoginListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the package's event subscribers and listeners.
     */
    public function boot(Dispatcher $events): void
    {
        $events->subscribe(ActivationListener::class);
        $events->listen(Login::class, TwoFactorLoginListener::class);
    }
}
