<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\SecurityResetService;

use Brackets\AdminAuth\Services\SecurityResetService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Illuminate\Database\DatabaseManager;

class PreviewTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testTotpAndPasskeysAreListed(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $this->addPasskey($user, 'MacBook');
        $this->addPasskey($user, 'YubiKey');

        $preview = $this->service()->preview($user->fresh());

        self::assertNotNull($preview);
        self::assertTrue($preview->totp);
        self::assertSame(['MacBook', 'YubiKey'], $preview->passkeys);
        self::assertFalse($preview->isEmpty());
    }

    public function testAdminWithoutFactorsHasNothingToReset(): void
    {
        $preview = $this->service()->preview($this->createAdmin());

        self::assertNotNull($preview);
        self::assertTrue($preview->isEmpty());
    }

    public function testMissingColumnsStillListThePasskeys(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');
        $this->dropTwoFactorColumns();

        $preview = $this->service()->preview($user->fresh());

        self::assertNotNull($preview);
        self::assertFalse($preview->totp);
        self::assertSame(['MacBook'], $preview->passkeys);
    }

    public function testMissingTableStillListsTotp(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $this->dropPasskeysTable();

        $preview = $this->service()->preview($user->fresh());

        self::assertNotNull($preview);
        self::assertTrue($preview->totp);
        self::assertSame([], $preview->passkeys);
    }

    public function testNeitherMigrationGivesNull(): void
    {
        $user = $this->createAdmin();
        $this->dropTwoFactorColumns();
        $this->dropPasskeysTable();

        self::assertNull($this->service()->preview($user->fresh()));
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
