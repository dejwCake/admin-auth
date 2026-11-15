<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\TwoFactorService;

use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;

class VerifyCodeTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testValidCodeIsAcceptedOnce(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $twoFactorService = $this->app->make(TwoFactorService::class);
        $code = $this->currentCode($user);

        self::assertTrue($twoFactorService->verifyCode($user, $code));
        self::assertFalse($twoFactorService->verifyCode($user, $code));
    }

    public function testInvalidCodeIsRejected(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);

        self::assertFalse($this->app->make(TwoFactorService::class)->verifyCode($user, $this->wrongCode($user)));
    }

    public function testAdminWithoutSecretIsRejected(): void
    {
        self::assertFalse($this->app->make(TwoFactorService::class)->verifyCode($this->createAdmin(), '123456'));
    }
}
