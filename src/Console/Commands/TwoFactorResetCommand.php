<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Console\Commands;

use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Services\PasskeyService;
use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Illuminate\Auth\AuthManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;

/**
 * Recovery path for an admin who lost both the device and the recovery codes.
 */
final class TwoFactorResetCommand extends Command
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $signature = 'admin-auth:two-factor-reset
        {email : E-mail of the admin}
        {--force : Reset without asking for confirmation}';

    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $description = 'Turn off two-factor authentication (TOTP secret, recovery codes, passkeys) for one admin';

    public function __construct(
        private readonly AuthManager $authManager,
        private readonly Config $config,
        private readonly TwoFactorService $twoFactorService,
        private readonly PasskeyService $passkeyService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $guard = $this->config->get('admin-auth.defaults.guard', 'admin');
        $provider = $this->authManager->createUserProvider(
            $this->config->get(sprintf('auth.guards.%s.provider', $guard)),
        );

        // Through the guard's user provider, so a project's own admin model works too.
        $user = $provider?->retrieveByCredentials(['email' => $email]);
        if ($user === null) {
            $this->error(sprintf('No admin with the e-mail %s was found.', $email));

            return self::FAILURE;
        }

        $factors = $this->factorsOf($user);
        if ($factors === null) {
            $this->error(trans('brackets/admin-auth::admin.two_factor.migration_missing'));

            return self::FAILURE;
        }

        if ($factors === []) {
            $this->info(sprintf('%s has no two-factor authentication, nothing to reset.', $email));

            return self::SUCCESS;
        }

        $this->line(sprintf('This removes from %s:', $email));
        foreach ($factors as $factor) {
            $this->line(sprintf('  - %s', $factor));
        }

        if (
            !$this->option('force')
            && !$this->confirm(sprintf('Turn off two-factor authentication for %s?', $email), false)
        ) {
            $this->info('Nothing was changed.');

            return self::SUCCESS;
        }

        $this->reset($user);

        $this->info(sprintf('Two-factor authentication for %s was turned off.', $email));

        return self::SUCCESS;
    }

    /**
     * What the admin has to reset, described; null when neither the two-factor columns nor the passkeys table exist.
     *
     * @return array<int, string>|null
     */
    private function factorsOf(Authenticatable $user): ?array
    {
        $hasTotpColumns = $user instanceof TwoFactorAuthenticatable && $this->twoFactorService->isAvailableFor($user);
        $hasPasskeysTable = $this->passkeyService->isAvailableFor($user);
        if (!$hasTotpColumns && !$hasPasskeysTable) {
            return null;
        }

        $factors = $this->hasTotpData($user) ? ['the authenticator app (TOTP) and the recovery codes'] : [];
        if ($user instanceof PasskeyAuthenticatable && $hasPasskeysTable) {
            foreach ($user->passkeys()->orderBy('id')->pluck('name') as $name) {
                $factors[] = sprintf('the passkey "%s"', $name);
            }
        }

        return $factors;
    }

    private function hasTotpData(Authenticatable $user): bool
    {
        return $user instanceof TwoFactorAuthenticatable
            && $this->twoFactorService->isAvailableFor($user)
            && $user->hasTwoFactorData();
    }

    private function reset(Authenticatable $user): void
    {
        if ($user instanceof Model && $user instanceof TwoFactorAuthenticatable && $this->hasTotpData($user)) {
            $this->twoFactorService->disable($user);
        }

        if ($user instanceof PasskeyAuthenticatable && $this->passkeyService->isAvailableFor($user)) {
            $user->passkeys()->delete();
        }
    }
}
