<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests;

abstract class BracketsTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->adminAuthGuard = config('admin-auth.defaults.guard');
    }
}
