<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\SecurityResetService;

use Brackets\AdminAuth\Services\SecurityResetService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Illuminate\Database\DatabaseManager;

class FactorsOfTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testTotpAndPasskeysAreListed(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $this->addPasskey($user, 'MacBook');
        $this->addPasskey($user, 'YubiKey');

        $factors = $this->service()->factorsOf($user->fresh());

        self::assertNotNull($factors);
        self::assertTrue($factors->totp);
        self::assertSame(['MacBook', 'YubiKey'], $factors->passkeys);
        self::assertFalse($factors->isEmpty());
    }

    public function testAdminWithoutFactorsHasNothingToReset(): void
    {
        $factors = $this->service()->factorsOf($this->createAdmin());

        self::assertNotNull($factors);
        self::assertTrue($factors->isEmpty());
    }

    public function testMissingColumnsStillListThePasskeys(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');
        $this->dropTwoFactorColumns();

        $factors = $this->service()->factorsOf($user->fresh());

        self::assertNotNull($factors);
        self::assertFalse($factors->totp);
        self::assertSame(['MacBook'], $factors->passkeys);
    }

    public function testMissingTableStillListsTotp(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $this->dropPasskeysTable();

        $factors = $this->service()->factorsOf($user->fresh());

        self::assertNotNull($factors);
        self::assertTrue($factors->totp);
        self::assertSame([], $factors->passkeys);
    }

    public function testNeitherMigrationGivesNull(): void
    {
        $user = $this->createAdmin();
        $this->dropTwoFactorColumns();
        $this->dropPasskeysTable();

        self::assertNull($this->service()->factorsOf($user->fresh()));
    }

    private function dropPasskeysTable(): void
    {
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');
    }

    private function service(): SecurityResetService
    {
        return $this->app->make(SecurityResetService::class);
    }
}
