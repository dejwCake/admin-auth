<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\TwoFactorService;

use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestUserModel;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;

class IsRequiredForTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testNotRequiredByDefault(): void
    {
        self::assertFalse($this->app->make(TwoFactorService::class)->isRequiredFor($this->createAdmin()));
    }

    public function testRequiredForEveryAdminWithoutRoles(): void
    {
        $this->requireTwoFactor();

        self::assertTrue($this->app->make(TwoFactorService::class)->isRequiredFor($this->createAdmin()));
    }

    public function testRequiredForAnAdminWithOneOfTheRoles(): void
    {
        $this->requireTwoFactor(['Administrator', 'Editor']);

        self::assertTrue(
            $this->app->make(TwoFactorService::class)->isRequiredFor($this->createRoleAdmin(['Editor'])),
        );
    }

    public function testNotRequiredForAnAdminWithoutTheRoles(): void
    {
        $this->requireTwoFactor(['Administrator']);

        self::assertFalse(
            $this->app->make(TwoFactorService::class)->isRequiredFor($this->createRoleAdmin(['Editor'])),
        );
    }

    public function testRequiredByRoleForAModelWithoutRolesFailsClosed(): void
    {
        $this->requireTwoFactor(['Administrator']);
        $user = TestUserModel::create(['email' => 'jane@example.com', 'password' => 'x']);

        self::assertTrue($this->app->make(TwoFactorService::class)->isRequiredFor($user));
    }
}
