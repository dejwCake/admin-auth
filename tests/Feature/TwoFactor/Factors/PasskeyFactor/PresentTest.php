<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\Factors\PasskeyFactor;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Factors\PasskeyFactor;
use Illuminate\Database\DatabaseManager;

class PresentTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testWithoutPasskeys(): void
    {
        $presentation = $this->app->make(PasskeyFactor::class)->present($this->createAdmin());

        self::assertSame('passkey', $presentation->key);
        self::assertSame('Passkeys', $presentation->name);
        self::assertTrue($presentation->available);
        self::assertFalse($presentation->active);
        self::assertSame('None', $presentation->status);
        self::assertNotNull($presentation->link);
        self::assertSame('http://localhost/admin/passkeys', $presentation->link->url);
        self::assertSame('Add a passkey', $presentation->link->label);
        self::assertNull($presentation->component);
    }

    public function testOnePasskey(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');

        $presentation = $this->app->make(PasskeyFactor::class)->present($user);

        self::assertTrue($presentation->active);
        self::assertSame('1 passkey', $presentation->status);
        self::assertNotNull($presentation->link);
        self::assertSame('Manage passkeys', $presentation->link->label);
    }

    public function testTwoPasskeys(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');
        $this->addPasskey($user, 'YubiKey');

        self::assertSame('2 passkeys', $this->app->make(PasskeyFactor::class)->present($user)->status);
    }

    public function testWithoutTheTable(): void
    {
        $user = $this->createAdmin();
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        $presentation = $this->app->make(PasskeyFactor::class)->present($user);

        self::assertFalse($presentation->available);
        self::assertFalse($presentation->active);
        self::assertSame(
            'Passkeys need a migration: publish the admin-auth migrations, then run migrate.',
            $presentation->notice,
        );
    }
}
