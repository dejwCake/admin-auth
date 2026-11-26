<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\TwoFactorService;

use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;

final class ForgetUnconfirmedEnrolmentTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testUnconfirmedSecretIsDropped(): void
    {
        $user = $this->createAdmin();
        $this->storeUnconfirmedSecret($user);

        $this->app->make(TwoFactorService::class)->forgetUnconfirmedEnrolment($user);

        self::assertFalse($user->fresh()?->hasTwoFactorData());
    }

    public function testConfirmedAuthenticatorStays(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->app->make(TwoFactorService::class)->forgetUnconfirmedEnrolment($user);

        self::assertTrue($user->fresh()?->hasTwoFactorEnabled());
    }
}
