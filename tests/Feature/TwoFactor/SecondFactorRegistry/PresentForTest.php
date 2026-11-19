<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\SecondFactorRegistry;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TestSecondFactor;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Factors\PasskeyFactor;
use Brackets\AdminAuth\TwoFactor\Factors\TotpFactor;
use Brackets\AdminAuth\TwoFactor\SecondFactorRegistry;
use Illuminate\Contracts\Config\Repository as Config;

class PresentForTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testOnePresentationPerFactorInOrder(): void
    {
        $this->app->make(Config::class)->set(
            'admin-auth.two_factor.factors',
            [TotpFactor::class, PasskeyFactor::class, TestSecondFactor::class],
        );

        $presentations = $this->app->make(SecondFactorRegistry::class)->presentFor($this->createAdmin());

        self::assertSame(
            ['totp', 'passkey', 'test'],
            array_column($presentations, 'key'),
        );
    }
}
