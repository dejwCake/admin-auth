<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Providers;

use Brackets\AdminAuth\Listeners\ActivationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The subscriber classes to register.
     *
     * @var array<class-string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $subscribe = [
        ActivationListener::class,
    ];
}
