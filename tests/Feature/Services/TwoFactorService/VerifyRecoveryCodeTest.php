<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\TwoFactorService;

use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;

class VerifyRecoveryCodeTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testOnlyTheUsedCodeIsConsumed(): void
    {
        $user = $this->createAdmin();
        [$first, $second] = $this->enrol($user);
        $twoFactorService = $this->app->make(TwoFactorService::class);

        self::assertTrue($twoFactorService->verifyRecoveryCode($user, $first));
        self::assertFalse($twoFactorService->verifyRecoveryCode($user->fresh(), $first));
        self::assertTrue($twoFactorService->verifyRecoveryCode($user->fresh(), $second));
    }

    public function testCodeIsCaseAndWhitespaceInsensitive(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);

        self::assertTrue(
            $this->app->make(TwoFactorService::class)
                ->verifyRecoveryCode($user, sprintf(' %s ', strtoupper(self::RECOVERY_CODE))),
        );
    }

    public function testUnknownCodeChangesNothing(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);

        self::assertFalse($this->app->make(TwoFactorService::class)->verifyRecoveryCode($user, 'zzzzz-zzzzz'));
        self::assertCount(2, $user->fresh()->getTwoFactorRecoveryCodes());
    }
}
