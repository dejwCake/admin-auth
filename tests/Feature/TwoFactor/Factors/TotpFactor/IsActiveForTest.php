<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\Factors\TotpFactor;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Factors\TotpFactor;

class IsActiveForTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testConfirmedTotp(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);

        self::assertTrue($this->app->make(TotpFactor::class)->isActiveFor($user->fresh()));
    }

    public function testWithoutTotp(): void
    {
        self::assertFalse($this->app->make(TotpFactor::class)->isActiveFor($this->createAdmin()));
    }

    public function testWithoutTheColumns(): void
    {
        $user = $this->createAdmin();
        $this->dropTwoFactorTable();

        self::assertFalse($this->app->make(TotpFactor::class)->isActiveFor($user->fresh()));
    }
}
