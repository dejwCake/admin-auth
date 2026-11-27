<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Customisation;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestConnectionAdminUserModel;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
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

    private const string RECOVERY_CODE = 'aaaaa-bbbbb';

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $config = $this->app->make(Config::class);
        $config->set(sprintf('database.connections.%s', TestConnectionAdminUserModel::CONNECTION), [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $config->set('auth.providers.admin_users.model', TestConnectionAdminUserModel::class);
        $this->app->make(AuthFactory::class)->forgetGuards();

        $defaultSchema = $this->db()->connection()->getSchemaBuilder();
        $defaultSchema->dropIfExists('admin_two_factors');
        $defaultSchema->dropIfExists('admin_passkeys');

        $schema = $this->db()->connection(TestConnectionAdminUserModel::CONNECTION)->getSchemaBuilder();
        $schema->create('test_brackets_user_models', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->boolean('activated')->default(false);
            $table->boolean('forbidden')->default(false);
            $table->string('language', 2)->default('en');
            $table->timestamp('last_login_at')->nullable();
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
        $admin = $this->createAdminWithPasskey(new VirtualAuthenticator());

        self::assertTrue($this->passkeyService()->hasPasskeys($admin));
        self::assertTrue($this->passkeyService()->isAvailable());
    }

    public function testPasskeySignsInOnTheAdminConnection(): void
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);
        $authenticator = new VirtualAuthenticator();
        $admin = $this->createAdminWithPasskey($authenticator);

        $options = $this->postJson('/admin/login/passkey/options')->assertOk()->json();
        $this->postJson('/admin/login/passkey', [
            'credential' => $this->assertion($authenticator, $options, $admin),
        ])->assertNoContent();

        self::assertSame($admin->getAuthIdentifier(), $this->loggedInUser()?->getAuthIdentifier());
    }

    private function createAdmin(): TestConnectionAdminUserModel
    {
        $admin = new TestConnectionAdminUserModel();
        $admin->forceFill([
            'email' => 'john@example.com',
            'password' => 'secret',
            'activated' => true,
        ])->save();

        return $admin;
    }

    private function createAdminWithPasskey(VirtualAuthenticator $authenticator): TestConnectionAdminUserModel
    {
        $admin = $this->createAdmin();
        $options = $this->passkeyService()->registrationOptions($this->passkeySession(), $admin);
        $this->passkeyService()->register(
            $this->passkeySession(),
            $admin,
            $authenticator->register($options, $this->origin()),
            'MacBook',
        );

        return $admin;
    }

    private function createEnrolledAdmin(): TestConnectionAdminUserModel
    {
        $admin = $this->createAdmin();
        $admin->twoFactor()->make()->forceFill([
            'secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
            'recovery_codes' => [$this->app->make(Hasher::class)->make(self::RECOVERY_CODE)],
            'confirmed_at' => CarbonImmutable::now(),
        ])->save();

        return $admin;
    }

    private function loggedInUser(): mixed
    {
        return $this->app->make(AuthFactory::class)->guard($this->adminAuthGuard)->user();
    }

    private function db(): DatabaseManager
    {
        return $this->app->make(DatabaseManager::class);
    }
}
