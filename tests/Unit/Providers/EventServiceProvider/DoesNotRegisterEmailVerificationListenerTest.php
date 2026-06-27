<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Providers\EventServiceProvider;

use Brackets\AdminAuth\Providers\EventServiceProvider;
use Brackets\AdminAuth\Tests\TestCase;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class DoesNotRegisterEmailVerificationListenerTest extends TestCase
{
    /**
     * Extending Laravel's app-level EventServiceProvider made this provider re-run
     * configureEmailVerification(), registering SendEmailVerificationNotification a
     * second time in the host app and sending the verification email twice. It must
     * stay a plain ServiceProvider so that side effect never comes back.
     */
    public function testProviderExtendsThePlainServiceProvider(): void
    {
        self::assertSame(ServiceProvider::class, get_parent_class(EventServiceProvider::class));
    }

    public function testBootDoesNotRegisterAnyRegisteredEventListener(): void
    {
        $events = new Dispatcher($this->app);

        (new EventServiceProvider($this->app))->boot($events);

        self::assertSame([], $events->getListeners(Registered::class));
    }
}
