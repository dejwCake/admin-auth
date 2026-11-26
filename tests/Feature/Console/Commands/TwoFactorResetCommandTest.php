<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Console\Commands;

use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;

class TwoFactorResetCommandTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testResetsAnEnrolledAdmin(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com', '--force' => true])
            ->assertExitCode(0);

        $fresh = $user->fresh();
        self::assertFalse($fresh->hasTwoFactorEnabled());
        self::assertNull($fresh->getTwoFactorSecret());
        self::assertSame([], $fresh->getTwoFactorRecoveryCodes());

        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);
        $this->get('/admin')->assertStatus(200);
    }

    public function testClearsTheReplayAndThrottleState(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $twoFactorService = $this->app->make(TwoFactorService::class);
        $twoFactorService->verifyCode($user, $this->currentCode($user));
        $rateLimiter = $this->app->make(RateLimiter::class);
        $rateLimiter->hit($twoFactorService->throttleKey($user));

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com', '--force' => true])
            ->assertExitCode(0);

        self::assertFalse(
            $this->app->make(Cache::class)->has(sprintf('admin-auth:two-factor:last:%s', $user->getKey())),
        );
        self::assertSame(0, $rateLimiter->attempts($twoFactorService->throttleKey($user)));
    }

    public function testResetsAForcedAdminWhoMustEnrolAgain(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com', '--force' => true])
            ->assertExitCode(0);

        self::assertFalse($user->fresh()->hasTwoFactorEnabled());
        $this->actingAs($user->fresh(), $this->adminAuthGuard)
            ->get('/admin')
            ->assertRedirect(route('brackets/admin-auth::admin/two-factor'));
    }

    public function testUnknownEmailFails(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'nobody@example.com', '--force' => true])
            ->expectsOutputToContain('No admin with the e-mail nobody@example.com was found.')
            ->assertExitCode(1);

        self::assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function testAdminWithoutTwoFactorHasNothingToReset(): void
    {
        $this->createAdmin();

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com'])
            ->expectsOutputToContain('nothing to reset')
            ->assertExitCode(0);
    }

    public function testDeclinedConfirmationChangesNothing(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com'])
            ->expectsConfirmation('Turn off two-factor authentication for john@example.com?', 'no')
            ->assertExitCode(0);

        self::assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function testMissingColumnsAndTableFail(): void
    {
        $this->createAdmin();
        $this->dropTwoFactorTable();
        $this->dropPasskeysTable();

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com', '--force' => true])
            ->expectsOutputToContain('migration')
            ->assertExitCode(1);
    }

    public function testResetsTotpAndEveryPasskey(): void
    {
        $user = $this->createAdmin();
        $this->enrol($user);
        $this->addPasskey($user, 'MacBook');
        $this->addPasskey($user, 'YubiKey');
        $other = $this->addPasskey($this->createAdmin('jane@example.com'), 'Jane phone');

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com', '--force' => true])
            ->expectsOutputToContain('the passkey "MacBook"')
            ->expectsOutputToContain('the passkey "YubiKey"')
            ->assertExitCode(0);

        self::assertFalse($user->fresh()->hasTwoFactorData());
        self::assertSame(0, $user->passkeys()->count());
        self::assertNotNull($other->fresh());
    }

    public function testResetsAnAdminWithPasskeysOnly(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user);

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com', '--force' => true])
            ->assertExitCode(0);

        self::assertSame(0, $user->passkeys()->count());
    }

    public function testConfirmationListsThePasskeysAndDeclineKeepsThem(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com'])
            ->expectsOutputToContain('the passkey "MacBook"')
            ->expectsConfirmation('Turn off two-factor authentication for john@example.com?', 'no')
            ->assertExitCode(0);

        self::assertSame(1, $user->passkeys()->count());
    }

    public function testMissingColumnsWithThePasskeysTableRemovesThePasskeys(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user);
        $this->dropTwoFactorTable();

        $this->artisan('admin-auth:two-factor-reset', ['email' => 'john@example.com', '--force' => true])
            ->assertExitCode(0);

        self::assertSame(0, $user->passkeys()->count());
    }

    private function dropPasskeysTable(): void
    {
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');
    }
}
