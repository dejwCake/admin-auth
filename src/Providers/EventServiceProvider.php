<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Providers;

use Brackets\AdminAuth\Listeners\ActivationListener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the package's event subscribers.
     */
    public function boot(Dispatcher $events): void
    {
        $events->subscribe(ActivationListener::class);
    }
}
