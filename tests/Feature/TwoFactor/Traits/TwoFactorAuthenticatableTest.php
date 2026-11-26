<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\TwoFactor\Traits;

use Brackets\AdminAuth\Models\AdminTwoFactor;
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

        $stored = $this->db()->table('admin_two_factors')->where('admin_user_id', $user->getKey())->value('secret');

        self::assertIsString($stored);
        self::assertStringNotContainsString(self::SECRET, $stored);
        self::assertSame(self::SECRET, $user->fresh()->getTwoFactorSecret());
    }

    public function testSerialisedUserHoldsNoTwoFactorData(): void
    {
        $user = $this->createEnrolledUser()->fresh();
        $user->load('twoFactor');

        $json = $user->toJson();

        self::assertStringNotContainsString(self::SECRET, $json);
        self::assertArrayNotHasKey('two_factor', $user->toArray());
        self::assertArrayNotHasKey('twoFactor', $user->toArray());
    }

    public function testEnrolledUserHasTwoFactorEnabled(): void
    {
        self::assertTrue($this->createEnrolledUser()->fresh()->hasTwoFactorEnabled());
    }

    public function testUnconfirmedSecretIsNotEnabled(): void
    {
        $user = $this->createEnrolledUser();
        $this->db()->table('admin_two_factors')->where('admin_user_id', $user->getKey())->update([
            'confirmed_at' => null,
        ]);

        $fresh = $user->fresh();

        self::assertFalse($fresh->hasTwoFactorEnabled());
        self::assertTrue($fresh->hasTwoFactorData());
    }

    public function testUndecryptableSecretCountsAsNotEnabled(): void
    {
        $user = $this->createEnrolledUser();
        $this->db()->table('admin_two_factors')->where('admin_user_id', $user->getKey())->update([
            'secret' => 'not-an-encrypted-value',
        ]);

        $fresh = $user->fresh();

        self::assertNull($fresh->getTwoFactorSecret());
        self::assertFalse($fresh->hasTwoFactorEnabled());
    }

    public function testRecordIsLoadedOnce(): void
    {
        $user = $this->createEnrolledUser()->fresh();
        $connection = $this->db()->connection();
        $connection->enableQueryLog();

        $user->hasTwoFactorEnabled();
        $user->getTwoFactorRecoveryCodes();
        $user->hasTwoFactorData();

        $queries = array_filter(
            $connection->getQueryLog(),
            // Reads of the table itself; the schema check (once per request) names it too.
            static fn (array $query): bool => preg_match('/from [`"]admin_two_factors[`"]/', $query['query']) === 1,
        );
        self::assertCount(1, $queries);
    }

    public function testWithoutTheTableNothingIsQueried(): void
    {
        $user = $this->createAdmin();
        $this->db()->connection()->getSchemaBuilder()->drop('admin_two_factors');

        self::assertFalse($user->hasTwoFactorTable());
        self::assertNull($user->getTwoFactorRecord());
        self::assertFalse($user->hasTwoFactorEnabled());
    }

    private function createAdmin(): TestAdminUserModel
    {
        return TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);
    }

    private function createEnrolledUser(): TestAdminUserModel
    {
        $user = $this->createAdmin();
        $record = new AdminTwoFactor();
        $record->forceFill([
            'admin_user_id' => $user->getKey(),
            'secret' => self::SECRET,
            'recovery_codes' => [$this->app->make(Hasher::class)->make('aaaaa-bbbbb')],
            'confirmed_at' => CarbonImmutable::now(),
        ])->save();

        return $user;
    }

    private function db(): DatabaseManager
    {
        return $this->app->make(DatabaseManager::class);
    }
}
