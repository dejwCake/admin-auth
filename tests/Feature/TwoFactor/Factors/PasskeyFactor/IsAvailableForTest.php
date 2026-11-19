<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\Factors\PasskeyFactor;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Factors\PasskeyFactor;
use Illuminate\Database\DatabaseManager;

class IsAvailableForTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testWithTheTable(): void
    {
        self::assertTrue($this->app->make(PasskeyFactor::class)->isAvailableFor($this->createAdmin()));
    }

    public function testWithoutTheTable(): void
    {
        $user = $this->createAdmin();
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        self::assertFalse($this->app->make(PasskeyFactor::class)->isAvailableFor($user));
    }
}
