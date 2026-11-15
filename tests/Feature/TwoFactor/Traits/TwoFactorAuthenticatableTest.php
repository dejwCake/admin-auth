<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\Traits;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;

class TwoFactorAuthenticatableTest extends AdminUserTestCase
{
    private const string SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    public function testSecretIsStoredEncrypted(): void
    {
        $user = $this->createEnrolledUser();

        $stored = $this->app->make(DatabaseManager::class)->connection()
            ->table('test_brackets_user_models')
            ->where('id', $user->getKey())
            ->value('two_factor_secret');

        self::assertIsString($stored);
        self::assertStringNotContainsString(self::SECRET, $stored);
        self::assertSame(self::SECRET, $user->fresh()->getTwoFactorSecret());
    }

    public function testSerialisedUserHoldsNoTwoFactorData(): void
    {
        $user = $this->createEnrolledUser()->fresh();

        $json = $user->toJson();

        self::assertStringNotContainsString('two_factor_secret', $json);
        self::assertStringNotContainsString('two_factor_recovery_codes', $json);
        self::assertStringNotContainsString('two_factor_confirmed_at', $json);
        self::assertArrayNotHasKey('two_factor_secret', $user->toArray());
    }

    public function testEnrolledUserHasTwoFactorEnabled(): void
    {
        self::assertTrue($this->createEnrolledUser()->fresh()->hasTwoFactorEnabled());
    }

    public function testUnconfirmedSecretIsNotEnabled(): void
    {
        $user = $this->createEnrolledUser();
        $user->forceFill(['two_factor_confirmed_at' => null])->save();

        self::assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function testUndecryptableSecretCountsAsNotEnabled(): void
    {
        $user = $this->createEnrolledUser();
        $this->app->make(DatabaseManager::class)->connection()
            ->table('test_brackets_user_models')
            ->where('id', $user->getKey())
            ->update(['two_factor_secret' => 'not-an-encrypted-value']);

        $fresh = $user->fresh();

        self::assertNull($fresh->getTwoFactorSecret());
        self::assertFalse($fresh->hasTwoFactorEnabled());
    }

    private function createEnrolledUser(): TestAdminUserModel
    {
        $user = TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);
        $user->forceFill([
            'two_factor_secret' => self::SECRET,
            'two_factor_recovery_codes' => [$this->app->make(Hasher::class)->make('aaaaa-bbbbb')],
            'two_factor_confirmed_at' => CarbonImmutable::now(),
        ])->save();

        return $user;
    }
}
