<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests;

use Brackets\AdminAuth\Models\AdminTwoFactor;
use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Brackets\AdminAuth\Tests\Models\TestRoleAdminUserModel;
use Brackets\AdminAuth\Tests\Models\TestUserModel;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use PragmaRX\Google2FA\Google2FA;

/**
 * Creates admins of the tested guard (admin or web) and enrols them in two-factor.
 */
trait TwoFactorHelpers
{
    protected const string PASSWORD = 'testpass123';

    /**
     * The first of the recovery codes enrol() stores.
     */
    protected const string RECOVERY_CODE = 'aaaaa-bbbbb';

    protected function allowAdmin(): void
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);
    }

    protected function createAdmin(
        string $email = 'john@example.com',
    ): Model&TwoFactorAuthenticatable&PasskeyAuthenticatable {
        $password = $this->app->make(Hasher::class)->make(self::PASSWORD);

        $user = $this->adminAuthGuard === 'web'
            ? TestUserModel::create(['email' => $email, 'password' => $password])
            : TestAdminUserModel::create([
                'email' => $email,
                'password' => $password,
                'activated' => true,
                'forbidden' => false,
            ]);

        // Loaded from the database like the auth guard does, so the two-factor columns are present.
        return $user->fresh();
    }

    /**
     * Enrol without verifying a code, so the current code is still unused; returns the plain recovery codes.
     *
     * @return array<int, string>
     */
    protected function enrol(Model&TwoFactorAuthenticatable $user): array
    {
        $hasher = $this->app->make(Hasher::class);
        $recoveryCodes = [self::RECOVERY_CODE, 'ccccc-ddddd'];

        $this->storeTwoFactor($user, [
            'secret' => $this->app->make(Google2FA::class)->generateSecretKey(),
            'recovery_codes' => array_map(static fn (string $code): string => $hasher->make($code), $recoveryCodes),
            'confirmed_at' => CarbonImmutable::now(),
        ]);

        return $recoveryCodes;
    }

    protected function currentCode(Model&TwoFactorAuthenticatable $user): string
    {
        return $this->app->make(Google2FA::class)->getCurrentOtp((string) $user->getTwoFactorSecret());
    }

    protected function wrongCode(Model&TwoFactorAuthenticatable $user): string
    {
        return $this->currentCode($user) === '000000' ? '111111' : '000000';
    }

    protected function requireTwoFactor(array $roles = []): void
    {
        $this->app['config']->set('admin-auth.two_factor.required', true);
        $this->app['config']->set('admin-auth.two_factor.required_roles', $roles);
    }

    /**
     * An admin-guard admin with roles held in memory.
     *
     * @param array<int, string> $roles
     */
    protected function createRoleAdmin(array $roles): TestRoleAdminUserModel
    {
        $user = TestRoleAdminUserModel::create([
            'email' => 'role@example.com',
            'password' => $this->app->make(Hasher::class)->make(self::PASSWORD),
            'activated' => true,
            'forbidden' => false,
        ])->fresh();

        return $user->setTestRoles($roles);
    }

    /**
     * An authenticator enrolment that was started but never confirmed.
     */
    protected function storeUnconfirmedSecret(Model&TwoFactorAuthenticatable $user): void
    {
        $this->storeTwoFactor($user, [
            'secret' => 'JBSWY3DPEHPK3PXP',
            'recovery_codes' => null,
            'confirmed_at' => null,
        ]);
    }

    /**
     * Write the admin's two-factor record as TwoFactorService does, keeping the loaded relation in step.
     *
     * @param array<string, string|array<int, string>|CarbonImmutable|null> $attributes
     */
    protected function storeTwoFactor(Model&TwoFactorAuthenticatable $user, array $attributes): AdminTwoFactor
    {
        $record = $user->getTwoFactorRecord() ?? $user->twoFactor()->make();
        $record->forceFill($attributes)->save();
        $user->setRelation('twoFactor', $record);

        return $record;
    }

    /**
     * The database as before the two-factor migration.
     */
    protected function dropTwoFactorTable(): void
    {
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->drop('admin_two_factors');
    }
}
