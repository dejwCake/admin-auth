<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests;

use Override;

abstract class AdminUserTestCase extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->adminAuthGuard = config('admin-auth.defaults.guard');
    }
}
