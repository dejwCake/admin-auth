<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\Factors\TotpFactor;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Factors\TotpFactor;

class IsAvailableForTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testWithTheColumns(): void
    {
        self::assertTrue($this->app->make(TotpFactor::class)->isAvailableFor($this->createAdmin()));
    }

    public function testWithoutTheColumns(): void
    {
        $user = $this->createAdmin();
        $this->dropTwoFactorTable();

        self::assertFalse($this->app->make(TotpFactor::class)->isAvailableFor($user->fresh()));
    }
}
