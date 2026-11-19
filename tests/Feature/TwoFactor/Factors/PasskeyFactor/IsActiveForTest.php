<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\Factors\PasskeyFactor;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Factors\PasskeyFactor;
use Illuminate\Database\DatabaseManager;

class IsActiveForTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testWithAPasskey(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');

        self::assertTrue($this->app->make(PasskeyFactor::class)->isActiveFor($user));
    }

    public function testWithoutPasskeys(): void
    {
        self::assertFalse($this->app->make(PasskeyFactor::class)->isActiveFor($this->createAdmin()));
    }

    public function testWithoutTheTable(): void
    {
        $user = $this->createAdmin();
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        self::assertFalse($this->app->make(PasskeyFactor::class)->isActiveFor($user));
    }
}
