<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\SecurityResetService;

use Brackets\AdminAuth\Services\SecurityResetService;
use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Illuminate\Cache\RateLimiter;
use Illuminate\Database\DatabaseManager;

class ResetTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testRemovesTotpAndEveryPasskey(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $this->addPasskey($user, 'MacBook');
        $this->addPasskey($user, 'YubiKey');
        $other = $this->addPasskey($this->createAdmin('jane@example.com'), 'Jane phone');

        $removed = $this->service()->reset($user->fresh());

        self::assertTrue($removed->totp);
        self::assertSame(['MacBook', 'YubiKey'], $removed->passkeys);
        self::assertFalse($user->fresh()->hasTwoFactorData());
        self::assertSame(0, $user->passkeys()->count());
        self::assertNotNull($other->fresh());
    }

    public function testClearsTheChallengeThrottle(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $rateLimiter = $this->app->make(RateLimiter::class);
        $throttleKey = $this->app->make(TwoFactorService::class)->throttleKey($user);
        $rateLimiter->hit($throttleKey, 60);

        $this->service()->reset($user->fresh());

        self::assertSame(0, $rateLimiter->attempts($throttleKey));
    }

    public function testAdminWithoutFactorsIsLeftAsItIs(): void
    {
        $user = $this->createAdmin();

        $removed = $this->service()->reset($user);

        self::assertTrue($removed->isEmpty());
    }

    public function testMissingColumnsRemoveThePasskeys(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user);
        $this->dropTwoFactorTable();

        $removed = $this->service()->reset($user->fresh());

        self::assertFalse($removed->totp);
        self::assertSame(0, $user->passkeys()->count());
    }

    public function testNeitherMigrationResetsNothingWithoutAnError(): void
    {
        $user = $this->createAdmin();
        $this->dropTwoFactorTable();
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        self::assertTrue($this->service()->reset($user->fresh())->isEmpty());
    }

    private function service(): SecurityResetService
    {
        return $this->app->make(SecurityResetService::class);
    }
}
