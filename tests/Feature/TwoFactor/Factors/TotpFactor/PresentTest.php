<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\Factors\TotpFactor;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Factors\TotpFactor;

class PresentTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testNotSetUp(): void
    {
        $presentation = $this->app->make(TotpFactor::class)->present($this->createAdmin());

        self::assertSame('totp', $presentation->key);
        self::assertSame('Authenticator app', $presentation->name);
        self::assertTrue($presentation->available);
        self::assertFalse($presentation->active);
        self::assertSame('Not set up', $presentation->status);
        self::assertSame('two-factor-settings', $presentation->component);
        self::assertFalse($presentation->props['enabled']);
        self::assertSame('http://localhost/admin/two-factor/enable', $presentation->props['enableUrl']);
        self::assertIsArray($presentation->props['translations']);
        self::assertSame('Authenticator app', $presentation->props['translations']['title']);
        self::assertSame('Not set up', $presentation->props['translations']['status_off']);
    }

    public function testOn(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);

        $presentation = $this->app->make(TotpFactor::class)->present($user->fresh());

        self::assertTrue($presentation->active);
        self::assertSame('On', $presentation->status);
        self::assertTrue($presentation->props['enabled']);
        self::assertTrue($presentation->props['canDisable']);
    }

    public function testRequiredWithoutAnotherFactorCannotBeDisabled(): void
    {
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $this->enrol($user);

        $presentation = $this->app->make(TotpFactor::class)->present($user->fresh());

        self::assertTrue($presentation->props['required']);
        self::assertFalse($presentation->props['canDisable']);
    }

    public function testWithoutTheColumns(): void
    {
        $user = $this->createAdmin();
        $this->dropTwoFactorTable();

        $presentation = $this->app->make(TotpFactor::class)->present($user->fresh());

        self::assertFalse($presentation->available);
        self::assertFalse($presentation->active);
        self::assertSame(
            'Two-factor needs a migration: publish the admin-auth migrations, then run migrate.',
            $presentation->notice,
        );
    }
}
