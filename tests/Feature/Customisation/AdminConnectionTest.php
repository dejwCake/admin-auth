<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Customisation;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Override;

/**
 * An admin model on its own connection: the second factors are read and checked on that connection, not the
 * default one. The default connection has no second-factor tables here.
 */
class AdminConnectionTest extends AdminUserTestCase
{
    use PasskeyHelpers;

    private const string CONNECTION = 'admins';
    private const string RECOVERY_CODE = 'aaaaa-bbbbb';

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->app->make(Config::class)->set(sprintf('database.connections.%s', self::CONNECTION), [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $defaultSchema = $this->db()->connection()->getSchemaBuilder();
        $defaultSchema->dropIfExists('admin_two_factors');
        $defaultSchema->dropIfExists('admin_passkeys');

        $schema = $this->db()->connection(self::CONNECTION)->getSchemaBuilder();
        $schema->create('test_brackets_user_models', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email');
            $table->string('password');
            $table->boolean('activated')->default(false);
            $table->boolean('forbidden')->default(false);
            $table->softDeletes('deleted_at');
            $table->timestamps();
        });
        $this->setUpSecondFactorTables($schema);
    }

    public function testTwoFactorIsReadOnTheAdminConnection(): void
    {
        self::assertTrue($this->createEnrolledAdmin()->hasTwoFactorEnabled());
    }

    public function testRecoveryCodeIsConsumedOnTheAdminConnection(): void
    {
        $record = $this->createEnrolledAdmin()->getTwoFactorRecord();
        self::assertNotNull($record);

        $consumed = $record->consumeRecoveryCode(
            fn (string $hash): bool => $this->app->make(Hasher::class)->check(self::RECOVERY_CODE, $hash),
        );

        self::assertNotNull($consumed);
        self::assertSame([], $record->fresh()?->recovery_codes);
    }

    public function testPasskeysAreAvailableOnTheAdminConnection(): void
    {
        self::assertTrue($this->passkeyService()->hasPasskeys($this->createAdminWithPasskey()));
        self::assertFalse($this->passkeyService()->isAvailable());
    }

    private function createAdminWithPasskey(): TestAdminUserModel
    {
        $admin = $this->createAdmin();
        $options = $this->passkeyService()->registrationOptions($this->passkeySession(), $admin);
        $this->passkeyService()->register(
            $this->passkeySession(),
            $admin,
            (new VirtualAuthenticator())->register($options, $this->origin()),
            'MacBook',
        );

        return $admin;
    }

    private function createAdmin(): TestAdminUserModel
    {
        $admin = new TestAdminUserModel();
        $admin->setConnection(self::CONNECTION);
        $admin->forceFill([
            'email' => 'john@example.com',
            'password' => 'secret',
            'activated' => true,
        ])->save();

        return $admin;
    }

    private function createEnrolledAdmin(): TestAdminUserModel
    {
        $admin = $this->createAdmin();
        $admin->twoFactor()->make()->forceFill([
            'secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
            'recovery_codes' => [$this->app->make(Hasher::class)->make(self::RECOVERY_CODE)],
            'confirmed_at' => CarbonImmutable::now(),
        ])->save();

        return $admin;
    }

    private function db(): DatabaseManager
    {
        return $this->app->make(DatabaseManager::class);
    }
}
