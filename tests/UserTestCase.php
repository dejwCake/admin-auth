<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests;

use Illuminate\Foundation\Application;
use Override;

abstract class UserTestCase extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->adminAuthGuard = config('admin-auth.defaults.guard');
    }

    /**
     * Before the application boots: the package routes read the guard when they are loaded.
     *
     * @param Application $app
     */
    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('admin-auth.defaults.guard', 'web');
        $app['config']->set('admin-auth.defaults.passwords', 'users');
        $app['config']->set('admin-auth.defaults.activations', 'users');
        $app['config']->set('admin-auth.check_forbidden', false);
        $app['config']->set('admin-auth.activation_enabled', false);
    }
}
