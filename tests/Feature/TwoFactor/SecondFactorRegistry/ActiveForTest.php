<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\SecondFactorRegistry;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Contracts\SecondFactor;
use Brackets\AdminAuth\TwoFactor\SecondFactorRegistry;
use Illuminate\Contracts\Auth\Authenticatable;

class ActiveForTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testNoFactor(): void
    {
        self::assertSame([], $this->activeKeys($this->createAdmin()));
    }

    public function testPasskeysOnly(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');

        self::assertSame(['passkey'], $this->activeKeys($user));
    }

    public function testBoth(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $this->addPasskey($user, 'MacBook');

        self::assertSame(['totp', 'passkey'], $this->activeKeys($user->fresh()));
    }

    public function testTotpWithoutTheColumnsIsNotActive(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');
        $this->dropTwoFactorTable();

        self::assertSame(['passkey'], $this->activeKeys($user->fresh()));
    }

    /**
     * @return array<int, string>
     */
    private function activeKeys(Authenticatable $user): array
    {
        return array_map(
            static fn (SecondFactor $factor): string => $factor->key(),
            $this->app->make(SecondFactorRegistry::class)->activeFor($user),
        );
    }
}
